<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthApiController extends Controller
{
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
     * @param  array{email: string, password: string}  $credentials
     */
    protected function authenticateUser(array $credentials): ?User
    {
        $users = $this->firestore->query('users', ['email' => $credentials['email']], 1);

        if (empty($users)) {
            return null;
        }

        $userData = $users[0];

        $active = (bool) ($userData['active'] ?? true);
        $password = (string) ($userData['password'] ?? '');

        if (! $active) {
            return null;
        }

        if ($password === '') {
            return null;
        }

        if (! password_verify($credentials['password'], $password)) {
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
