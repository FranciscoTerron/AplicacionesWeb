<?php

namespace App\Http\Requests\Api\Discount;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/discounts/validate
 *
 * Valida un código de descuento.
 * Opcionalmente verifica si aplica a un producto o categoría específica.
 * Auth: Requerido (cliente autenticado)
 */
class ValidateDiscountRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50'],
            'product_id' => ['sometimes', 'string'],
            'category_id' => ['sometimes', 'string'],
        ];
    }
}
