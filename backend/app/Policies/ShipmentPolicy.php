<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    public function view(User $user, Shipment $shipment): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->role === 'admin';
    }

    public function activate(User $user, Shipment $shipment): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, Shipment $shipment): bool
    {
        return false;
    }
}
