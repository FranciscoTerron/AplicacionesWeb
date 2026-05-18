<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Admin y Editor pueden ver la lista de pedidos.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Admin y Editor pueden ver detalles de un pedido.
     */
    public function view(User $user, Order $order): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Solo Admin puede crear pedidos.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Admin y Editor pueden actualizar pedidos (cambiar estado).
     * Editor SÍ puede cambiar el status (operación cotidiana: marcar pagada, en preparación, enviada).
     * Por eso la ruta PATCH orders/{id}/status queda fuera del grupo middleware admin.
     */
    public function update(User $user, Order $order): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Solo Admin puede cancelar pedidos.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->role === 'admin';
    }
}
