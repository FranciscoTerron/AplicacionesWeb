<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthApiController extends Controller
{
    /** Vigencia de un access token, en días. */
    private const TOKEN_TTL_DAYS = 30;

    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * POST /api/v1/auth/login
     *
     * Autenticación de usuarios (admin/editor) con token personal.
     */
    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'login',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales inválidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        $user = $this->authenticateUser($credentials);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        $plainTextToken = $this->generateToken();

        $this->firestore->createDocument('api_tokens', [
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'name' => 'api-token',
            'abilities' => ['*'],
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addDays(self::TOKEN_TTL_DAYS)->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'token' => $plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/register
     *
     * Registro de nuevos usuarios (clientes).
     */
    #[OA\Post(
        path: '/api/v1/auth/register',
        operationId: 'register',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registro exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $existingClients = $this->firestore->query('clients', ['email' => $request->email], 1);

        if (! empty($existingClients)) {
            return response()->json([
                'success' => false,
                'message' => 'El email ya está registrado',
            ], 422);
        }

        $userId = Str::random(20);
        $now = now()->toISOString();
        $plainTextToken = $this->generateToken();

        // createDocumentWithId fija el doc id de Firestore = $userId, de modo que
        // el user_id guardado en el token resuelva con getDocument('clients', $userId).
        // (createDocument autogenera otro doc id y rompía la autenticación posterior.)
        $this->firestore->createDocumentWithId('clients', $userId, [
            'id' => $userId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'role' => 'cliente',
            'active' => true,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->firestore->createDocument('api_tokens', [
            'user_id' => $userId,
            'token' => hash('sha256', $plainTextToken),
            'name' => 'api-token',
            'abilities' => ['*'],
            'created_at' => $now,
            'expires_at' => now()->addDays(self::TOKEN_TTL_DAYS)->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'token' => $plainTextToken,
            'user' => [
                'id' => $userId,
                'name' => $request->name,
                'email' => $request->email,
                'role' => 'cliente',
            ],
        ], 201);
    }

    /**
     * POST /api/v1/auth/refresh
     *
     * Renueva un token expirado usando refresh token.
     */
    #[OA\Post(
        path: '/api/v1/auth/refresh',
        operationId: 'refresh',
        tags: ['Auth'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token renovado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'token', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Refresh token inválido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function refresh(): JsonResponse
    {
        $refreshToken = request('refresh_token');

        if (empty($refreshToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token requerido',
            ], 401);
        }

        $hashedToken = hash('sha256', $refreshToken);
        $tokens = $this->firestore->query('api_tokens', ['token' => $hashedToken], 1);

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token inválido',
            ], 401);
        }

        $tokenData = $tokens[0];
        $plainTextToken = $this->generateToken();
        $now = now()->toISOString();

        $this->firestore->updateDocument('api_tokens', (string) ($tokenData['id'] ?? ''), [
            'token' => hash('sha256', $plainTextToken),
            'updated_at' => $now,
            'expires_at' => now()->addDays(self::TOKEN_TTL_DAYS)->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'token' => $plainTextToken,
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Revoca (elimina) el token con el que se autenticó la request.
     */
    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'logout',
        tags: ['Auth'],
        security: [new OA\Security(name: 'BearerAuth')],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            $hashedToken = hash('sha256', $token);
            $tokens = $this->firestore->query('api_tokens', ['token' => $hashedToken], 1);

            if (! empty($tokens)) {
                $this->firestore->deleteDocument('api_tokens', (string) ($tokens[0]['id'] ?? ''));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada',
        ]);
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     */
    protected function authenticateUser(array $credentials): ?User
    {
        // Try users first (admin/editor)
        $users = $this->firestore->query('users', ['email' => $credentials['email']], 1);

        if (! empty($users)) {
            $userData = $users[0];
            $active = (bool) ($userData['active'] ?? true);
            $password = (string) ($userData['password'] ?? '');

            if (! $active || $password === '' || ! password_verify($credentials['password'], $password)) {
                return null;
            }

            $user = new User;
            $user->forceFill([
                'id' => (string) ($userData['id'] ?? ''),
                'name' => (string) ($userData['name'] ?? ''),
                'email' => (string) ($userData['email'] ?? ''),
                'role' => (string) ($userData['role'] ?? 'cliente'),
                'active' => $active,
            ]);
            $user->exists = true;

            return $user;
        }

        // Try clients (e-commerce users)
        $clients = $this->firestore->query('clients', ['email' => $credentials['email']], 1);

        if (! empty($clients)) {
            $clientData = $clients[0];
            $active = (bool) ($clientData['active'] ?? true);
            $password = (string) ($clientData['password'] ?? '');

            if (! $active || $password === '' || ! password_verify($credentials['password'], $password)) {
                return null;
            }

            $user = new User;
            $user->forceFill([
                'id' => (string) ($clientData['id'] ?? ''),
                'name' => (string) ($clientData['name'] ?? ''),
                'email' => (string) ($clientData['email'] ?? ''),
                'role' => 'cliente',
                'active' => $active,
            ]);
            $user->exists = true;

            return $user;
        }

        return null;
    }

    protected function generateToken(): string
    {
        $tokenEntropy = Str::random(40);

        return sprintf(
            '%s%s%s',
            (string) config('sanctum.token_prefix', ''),
            $tokenEntropy,
            (string) hash('crc32b', $tokenEntropy)
        );
    }
}
