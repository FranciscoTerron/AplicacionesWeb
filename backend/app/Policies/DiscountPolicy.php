<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;

class DiscountPolicy
{
    /**
     * Determine whether the user can view any models.
     * Admins y Editors pueden ver lista de descuentos
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can view the model.
     * Admins y Editors pueden ver detalles de descuento
     */
    public function view(User $user, Discount $discount): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Determine whether the user can create models.
     * Solo Admin puede crear descuentos
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     * Solo Admin puede editar descuentos
     */
    public function update(User $user, Discount $discount): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     * Solo Admin puede eliminar (baja lógica) descuentos
     */
    public function delete(User $user, Discount $discount): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     * Solo Admin puede reactivar descuentos
     */
    public function restore(User $user, Discount $discount): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can activate a deactivated discount.
     * Solo Admin puede reactivar descuentos
     */
    public function activate(User $user, Discount $discount): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Nunca permitido para descuentos (siempre soft delete)
     */
    public function forceDelete(User $user, Discount $discount): bool
    {
        return false;
    }
}
