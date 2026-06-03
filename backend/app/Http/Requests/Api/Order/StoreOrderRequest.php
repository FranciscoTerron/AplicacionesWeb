<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/orders
 *
 * Crea una nueva orden desde el frontend.
 * Auth: Requerido (cliente autenticado)
 */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            // El precio NO se acepta del cliente: se resuelve server-side
            // contra Firestore para evitar manipulación del total.
            'shipping_address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:cash,card,transfer'],
        ];
    }
}
