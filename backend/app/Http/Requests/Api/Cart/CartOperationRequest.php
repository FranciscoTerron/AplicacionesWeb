<?php

namespace App\Http\Requests\Api\Cart;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida las operaciones del carrito (HU-B06).
 *
 * Refinamiento decidido: quantity min:1 para add (agregar 0 no tiene sentido);
 * para update se acepta 0 y equivale a quitar el ítem. El tope por stock no va
 * acá porque depende del producto: lo aplica el controller, que es quien lo lee.
 */
class CartOperationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:add,update,remove,clear'],
            'product_id' => ['required_unless:action,clear', 'string'],
            'quantity' => [
                'sometimes',
                'integer',
                $this->input('action') === 'update' ? 'min:0' : 'min:1',
            ],
        ];
    }
}
