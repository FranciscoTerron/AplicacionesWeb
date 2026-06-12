<?php

namespace App\Http\Requests\Api\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/wishlist
 *
 * Agrega un producto a la wishlist del usuario autenticado.
 */
class StoreWishlistRequest extends FormRequest
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
            'product_id' => ['required', 'string', 'max:255'],
        ];
    }
}
