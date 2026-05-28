<?php

namespace App\Http\Requests\Api\Catalog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/catalog/products
 *
 * Acepta filtros opcionales por query string:
 *  - search    (string)   Busca por nombre, SKU o descripción
 *  - category  (string)   ID de categoría para filtrar
 */
class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
