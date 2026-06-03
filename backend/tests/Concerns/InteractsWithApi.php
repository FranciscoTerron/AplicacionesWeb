<?php

namespace Tests\Concerns;

use App\Services\FirestoreService;
use Tests\Support\FakeFirestore;

/**
 * Infraestructura compartida para tests de integración de la API v1.
 *
 * - useFakeFirestore(): reemplaza FirestoreService por el doble in-memory.
 * - actingAsApiUser(): siembra un usuario + api_token y devuelve los headers
 *   con el Bearer token que el middleware AuthenticateApiToken espera.
 *
 * Flujo del middleware que esto satisface:
 *   bearerToken → hash('sha256') → query('api_tokens', token)
 *   → getDocument('users'|'clients', user_id) → setUserResolver
 */
trait InteractsWithApi
{
    protected FakeFirestore $firestore;

    protected ?string $apiUserId = null;

    /**
     * Bindea el doble in-memory de Firestore en el contenedor.
     */
    protected function useFakeFirestore(): FakeFirestore
    {
        $this->firestore = new FakeFirestore;
        $this->app->instance(FirestoreService::class, $this->firestore);

        return $this->firestore;
    }

    /**
     * Siembra un usuario autenticable + su api_token y devuelve los headers
     * de autenticación para usar en las requests del test.
     *
     * @param  array<string, mixed>  $overrides  Campos del usuario a sobreescribir.
     * @param  string  $collection  'clients' (default) o 'users'.
     * @return array<string, string>
     */
    protected function actingAsApiUser(array $overrides = [], string $collection = 'clients'): array
    {
        $userId = (string) ($overrides['id'] ?? 'user-1');

        $user = array_merge([
            'id' => $userId,
            'name' => 'Cliente Test',
            'email' => 'cliente@test.com',
            'role' => $collection === 'users' ? 'admin' : 'cliente',
            'active' => true,
        ], $overrides);

        $this->firestore->seed($collection, [$user]);

        $plainToken = 'test-token-'.$userId;
        $this->firestore->seed('api_tokens', [[
            'user_id' => $userId,
            'token' => hash('sha256', $plainToken),
            'name' => 'api-token',
            'abilities' => ['*'],
        ]]);

        $this->apiUserId = $userId;

        return ['Authorization' => 'Bearer '.$plainToken];
    }
}
