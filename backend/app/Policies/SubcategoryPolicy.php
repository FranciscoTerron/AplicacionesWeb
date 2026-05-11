<?php

namespace App\Policies;

use App\Models\Subcategory;
use App\Models\User;

class SubcategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Subcategory $subcategory): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can create models.
     * Admin y Editor pueden crear subcategorías
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can update the model.
     * Admin y Editor pueden editar subcategorías
     */
    public function update(User $user, Subcategory $subcategory): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can delete the model.
     * Admin y Editor pueden eliminar (baja lógica) subcategorías
     */
    public function delete(User $user, Subcategory $subcategory): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can activate the model.
     * Solo Admin puede reactivar subcategorías inactivas.
     */
    public function activate(User $user, Subcategory $subcategory): bool
    {
        return $user->role === 'admin';
    }

    public function restore(User $user, Subcategory $subcategory): bool
    {
        return false;
    }

    public function forceDelete(User $user, Subcategory $subcategory): bool
    {
        return false;
    }
}
