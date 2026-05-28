<?php

namespace App\Services;

use Exception;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthCheckService
{
    /**
     * Verifica el estado de salud de la API y sus servicios dependientes.
     */
    public function getStatus(): array
    {
        $services = [
            'api' => $this->checkApi(),
            'firestore' => $this->checkFirestore(),
        ];

        $overallStatus = collect($services)
            ->every(fn (string $status): bool => $status === 'online')
            ? 'online' : 'degraded';

        return [
            'success' => $overallStatus === 'online',
            'message' => $overallStatus === 'online' ? 'API working' : 'API partially available',
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ];
    }

    /**
     * Verifica que la instancia Laravel responda correctamente.
     *
     * En Vercel (entornos serverless) no existe loopback HTTP — el puerto es
     * ephemeral y no puede resolverse a si mismo.  En ese caso se verifica
     * alternativamente que Laravel este completamente cargado consultando
     * un valor de config: si config() funciona, el framework esta vivo.
     */
    protected function checkApi(): string
    {
        // En Vercel (serverless) no hay loopback; usar verificación por config
        if ($this->isRunningOnVercel()) {
            try {
                $timestamp = now()->toIso8601String();

                Log::debug('Health check (Vercel): Laravel processes running', ['timestamp' => $timestamp]);

                return 'online';
            } catch (Exception $e) {
                Log::warning('Health check (Vercel): Framework not fully loaded', ['error' => $e->getMessage()]);

                return 'offline';
            }
        }

        // Local / otros entornos: medir latencia con HTTP al endpoint /up de Laravel
        try {
            $start = microtime(true);

            Http::get(trim((string) config('app.url'), '/').'/up');

            $latencyMs = round((microtime(true) - $start) * 1000, 2);
            Log::debug('Health check: API responded', ['latency_ms' => $latencyMs]);

            return 'online';
        } catch (Exception $e) {
            Log::warning('Health check: API unreachable', ['error' => $e->getMessage()]);

            return 'offline';
        }
    }

    /**
     * Detecta si la aplicacion se esta ejecutando en Vercel.
     */
    protected function isRunningOnVercel(): bool
    {
        return (bool) config('services.vercel') || (bool) ($_SERVER['VERCEL'] ?? false);
    }

    /**
     * Verifica la conectividad con Firestore via la REST API.
     *
     * Hace una consulta LIMIT 1 contra la coleccion _health. Si las credenciales
     * son validas y alcanza la red, Firestore responde 200. Si no, captura
     * cualquier excepcion y reporta 'offline'.
     *
     * Usa la misma logica que FirestoreService para resolver projectId y token,
     * evitando depender del contenedor de Kreait en el constructor.
     */
    protected function checkFirestore(): string
    {
        try {
            $projectId = $this->resolveProjectId();

            if (! $projectId) {
                Log::warning('Health check: Firestore project ID no configurado');

                return 'offline';
            }

            $accessToken = $this->fetchAccessToken($projectId);

            // Endpoint REST de Firestore: runQuery
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery";

            $body = [
                'structuredQuery' => [
                    'from' => [['collectionId' => '_health']],
                    'limit' => 1,
                ],
            ];

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post($url, $body);

            if ($response->successful()) {
                Log::info('Health check: Firestore connected', ['project_id' => $projectId]);

                return 'online';
            }

            Log::warning('Health check: Firestore respondio con error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return 'offline';
        } catch (Throwable $e) {
            Log::warning('Health check: Firestore unreachable', [
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return 'offline';
        }
    }

    // ── helpers que replican la logica de FirestoreService ──────────────────

    protected function resolveProjectId(): ?string
    {
        $projectId = config('app.firestore_project_id');
        if ($projectId) {
            return $projectId;
        }

        $projectId = config('firebase.projects.app.project_id');
        if ($projectId) {
            return $projectId;
        }

        $path = storage_path('app/private/firebase-service-account.json');
        if (file_exists($path)) {
            $json = json_decode(file_get_contents($path), true);
            if (isset($json['project_id'])) {
                return $json['project_id'];
            }
        }

        return null;
    }

    protected function fetchAccessToken(string $projectId): string
    {
        $credentialsArray = null;
        $path = null;

        $firestoreCredentialsJson = config('app.firestore_credentials_json');
        if ($firestoreCredentialsJson) {
            $maybeArray = json_decode($firestoreCredentialsJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($maybeArray)) {
                $credentialsArray = $maybeArray;
            }
        }

        $firebaseCredentialsJson = config('app.firebase_credentials_json');
        if (! $credentialsArray && $firebaseCredentialsJson) {
            $maybeArray = json_decode($firebaseCredentialsJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($maybeArray)) {
                $credentialsArray = $maybeArray;
            }
        }

        $keyFile = config('app.firestore_key_file');
        if (! $credentialsArray && $keyFile && file_exists($keyFile)) {
            $path = $keyFile;
        }

        if (! $path && ! $credentialsArray) {
            $credentials = config('firebase.projects.app.credentials');
            if ($credentials) {
                if (is_string($credentials)) {
                    $maybeArray = json_decode($credentials, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($maybeArray)) {
                        $credentialsArray = $maybeArray;
                    } elseif (file_exists($credentials)) {
                        $path = $credentials;
                    }
                } elseif (is_array($credentials)) {
                    $credentialsArray = $credentials;
                }
            }
        }

        if (! $path && ! $credentialsArray) {
            $path = storage_path('app/private/firebase-service-account.json');
            if (! file_exists($path)) {
                throw new Exception("Firebase credentials file not found at: {$path}");
            }
        }

        $cred = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/datastore',
            $credentialsArray ?? $path
        );

        $token = $cred->fetchAuthToken();

        return $token['access_token'] ?? '';
    }
}
