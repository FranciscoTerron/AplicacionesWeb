<?php

namespace App\Services;

use Kreait\Firebase\Factory;

class FirebaseAuthService
{
    protected $auth;

    public function __construct()
    {
        $credentialsPath = storage_path('app/private/firebase-service-account.json');

        if (! file_exists($credentialsPath)) {
            throw new \Exception("Firebase credentials file not found at: {$credentialsPath}");
        }

        $factory = (new Factory)
            ->withServiceAccount($credentialsPath);

        $this->auth = $factory->createAuth();
    }

    public function verifyCredentials(string $email, string $password): ?array
    {
        try {
            $firestore = app(FirestoreService::class);
            $documents = $firestore->query('users', ['email' => $email], 1);

            foreach ($documents as $userData) {
                if (isset($userData['password']) && password_verify($password, $userData['password'])) {
                    return $userData + ['uid' => $userData['id']];
                }
            }

            return null;
        } catch (\Exception $e) {
            error_log('Firebase auth error: '.$e->getMessage());

            return null;
        }
    }

    public function createUser(array $data): array
    {
        try {
            $userRecord = $this->auth->createUser([
                'email' => $data['email'],
                'password' => $data['password'],
                'displayName' => $data['name'] ?? null,
            ]);

            return [
                'uid' => $userRecord->uid,
                'email' => $userRecord->email,
                'name' => $userRecord->displayName,
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getUser(string $uid): ?array
    {
        try {
            $userRecord = $this->auth->getUser($uid);

            return [
                'uid' => $userRecord->uid,
                'email' => $userRecord->email,
                'name' => $userRecord->displayName,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
