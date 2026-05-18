<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view any models.
     * Admin y Editor pueden ver lista de productos
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can view the model.
     * Admin y Editor pueden ver detalles de producto
     */
    public function view(User $user, Product $product): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can create models.
     * Admin y Editor pueden crear productos
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can update the model.
     * Admin y Editor pueden editar cualquier producto.
     */
    public function update(User $user, Product $product): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can delete the model.
     * Admin y Editor pueden dar baja lógica (active=false)
     */
    public function delete(User $user, Product $product): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can activate the model.
     * Solo Admin puede reactivar productos inactivos.
     */
    public function activate(User $user, Product $product): bool
    {
        return $user->role === 'admin';
    }

    // restore y forceDelete no se usan (baja lógica via update)
    public function restore(User $user, Product $product): bool
    {
        return false;
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return false;
    }
}
