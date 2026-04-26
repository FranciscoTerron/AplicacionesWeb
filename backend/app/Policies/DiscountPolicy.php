<?php

namespace App\Policies;

use App\Models\Discount;
use App\Models\User;

class DiscountPolicy
{
    /**
     * Determine whether the user can view any models.
     * Solo Admin puede ver lista de descuentos
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     * Solo Admin puede ver detalles de descuento
     */
    public function view(User $user, Discount $discount): bool
    {
        return $user->role === 'admin';
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

    public function restore(User $user, Discount $discount): bool { return false; }
    public function forceDelete(User $user, Discount $discount): bool { return false; }
}
