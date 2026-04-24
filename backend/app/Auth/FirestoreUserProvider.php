<?php

namespace App\Auth;

use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class FirestoreUserProvider implements UserProvider
{
    protected FirestoreService $fs;

    public function __construct()
    {
        $this->fs = app(FirestoreService::class);
    }

    public function retrieveById($identifier)
    {
        $doc = $this->fs->getDocument('users', $identifier);

        return $doc ? $this->getUserFromArray($doc) : null;
    }

    public function retrieveByToken($identifier, $token)
    {
        $doc = $this->fs->getDocument('users', $identifier);
        if ($doc && hash_equals($doc['remember_token'] ?? '', $token)) {
            return $this->getUserFromArray($doc);
        }

        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $this->fs->updateDocument('users', $user->getAuthIdentifier(), ['remember_token' => $token]);
    }

    public function retrieveByCredentials(array $credentials)
    {
        $email = $credentials['email'] ?? null;
        if (! $email) {
            return null;
        }

        $users = $this->fs->query('users', ['email' => $email], 1);

        return count($users) > 0 ? $this->getUserFromArray($users[0]) : null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $password = $credentials['password'] ?? null;
        if (! $password) {
            return false;
        }

        $doc = $this->fs->getDocument('users', $user->getAuthIdentifier());
        if ($doc && isset($doc['password'])) {
            return password_verify($password, $doc['password']);
        }

        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        $doc = $this->fs->getDocument('users', $user->getAuthIdentifier());
        if ($doc && isset($doc['password']) && password_needs_rehash($doc['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($credentials['password'], PASSWORD_DEFAULT);
            $this->fs->updateDocument('users', $user->getAuthIdentifier(), ['password' => $newHash]);

            return true;
        }

        return false;
    }

    protected function getUserFromArray(array $data)
    {
        $user = new User;
        $user->forceFill([
            'id' => (string) $data['id'],
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'role' => $data['role'] ?? 'editor',
            'active' => $data['active'] ?? true,
        ]);
        $user->exists = true;

        return $user;
    }
}
