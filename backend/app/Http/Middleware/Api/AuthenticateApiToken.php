<?php

namespace App\Http\Middleware\Api;

use App\Models\User;
use App\Services\FirestoreService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => ['Token not provided'],
            ], 401);
        }

        $hashedToken = hash('sha256', $token);
        $tokens = $this->firestore->query('api_tokens', ['token' => $hashedToken], 1);

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => ['Invalid token'],
            ], 401);
        }

        $tokenData = $tokens[0];

        $expiresAt = $tokenData['expires_at'] ?? null;
        if ($expiresAt && now()->greaterThan(Carbon::parse($expiresAt))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => ['Token expired'],
            ], 401);
        }

        $userId = (string) ($tokenData['user_id'] ?? '');

        if ($userId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => ['Invalid token'],
            ], 401);
        }

        $userDoc = $this->firestore->getDocument('users', $userId);

        // Try clients if not found in users
        if (! $userDoc) {
            $userDoc = $this->firestore->getDocument('clients', $userId);
        }

        if (! $userDoc || ! ($userDoc['active'] ?? true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => ['User not found or inactive'],
            ], 401);
        }

        $user = new User;
        $user->forceFill([
            'id' => $userDoc['id'],
            'name' => (string) ($userDoc['name'] ?? ''),
            'email' => (string) ($userDoc['email'] ?? ''),
            'role' => (string) ($userDoc['role'] ?? 'cliente'),
            'active' => (bool) ($userDoc['active'] ?? true),
        ]);
        $user->exists = true;

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
