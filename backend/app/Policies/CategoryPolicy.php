<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
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
    public function view(User $user, Category $category): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can create models.
     * Solo Admin puede crear categorías
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     * Solo Admin puede editar categorías
     */
    public function update(User $user, Category $category): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     * Solo Admin puede eliminar (baja lógica) categorías
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->role === 'admin';
    }

    // restore y forceDelete no se usan (baja lógica via update active=false)
    public function restore(User $user, Category $category): bool
    {
        return false;
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return false;
    }
}
