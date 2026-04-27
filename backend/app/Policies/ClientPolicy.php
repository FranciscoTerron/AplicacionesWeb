<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     * Admin y Editor pueden ver la lista
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can view the model.
     * Admin y Editor pueden ver detalles
     */
    public function view(User $user, Client $client): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can create models.
     * Solo Admin puede crear clientes
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     * Solo Admin puede editar clientes
     */
    public function update(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     * Solo Admin puede eliminar (baja lógica) clientes
     */
    public function delete(User $user, Client $client): bool
    {
        return $user->role === 'admin';
    }

    // restore y forceDelete no se usan (baja lógica via update active=false)
    public function restore(User $user, Client $client): bool
    {
        return false;
    }

    public function forceDelete(User $user, Client $client): bool
    {
        return false;
    }
}
