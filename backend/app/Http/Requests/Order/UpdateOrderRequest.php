<?php

namespace App\Http\Requests\Order;

use App\Support\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clientId' => 'sometimes|required|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.productId' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unitPrice' => 'required|numeric|min:0',
            'status' => 'sometimes|required|in:'.implode(',', array_keys(OrderStatus::statuses())),
            'payment_status' => 'nullable|in:'.implode(',', array_keys(OrderStatus::paymentStatuses())),
            'paymentMethod' => 'nullable|in:cash,transfer,card,mercado_pago',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'clientId.required' => 'El cliente es obligatorio.',
            'items.required' => 'Debe agregar al menos un producto.',
            'items.min' => 'Debe agregar al menos un producto.',
            'items.*.productId.required' => 'El producto es obligatorio.',
            'items.*.quantity.required' => 'La cantidad es obligatoria.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.unitPrice.required' => 'El precio es obligatorio.',
            'items.*.unitPrice.min' => 'El precio no puede ser negativo.',
            'status.in' => 'Estado no válido.',
            'payment_status.in' => 'Estado de pago no válido.',
            'paymentMethod.in' => 'Método de pago no válido.',
            'notes.max' => 'Las notas no pueden tener más de 1000 caracteres.',
        ];
    }
}
