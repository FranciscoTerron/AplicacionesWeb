<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $authUser, $user): bool
    {
        $userId = is_array($user) ? ($user['id'] ?? '') : $user->getAuthIdentifier();

        return $authUser->role === 'admin' || $authUser->getAuthIdentifier() === $userId;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $authUser, $user): bool
    {
        $userId = is_array($user) ? ($user['id'] ?? '') : $user->getAuthIdentifier();

        return $authUser->role === 'admin' || $authUser->getAuthIdentifier() === $userId;
    }

    /**
     * Determine whether the user can delete the user (soft-delete/desactivate).
     */
    public function delete(User $authUser, $user): bool
    {
        $userId = is_array($user) ? ($user['id'] ?? '') : $user->getAuthIdentifier();

        // Admin can delete anyone except themselves
        if ($authUser->role === 'admin') {
            return $authUser->getAuthIdentifier() !== $userId;
        }

        // Users can delete (deactivate) their own profile
        return $authUser->getAuthIdentifier() === $userId;
    }

    /**
     * Determine whether the user can activate/deactivate users.
     */
    public function activate(User $authUser, $user): bool
    {
        // Only admins can activate/deactivate users
        return $authUser->role === 'admin';
    }
}
