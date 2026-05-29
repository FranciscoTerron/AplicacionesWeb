<?php

namespace App\Http\Requests\Api\Catalog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GET /api/v1/catalog/products
 *
 * Acepta filtros opcionales por query string:
 *  - search    (string)   Busca por nombre, SKU o descripción
 *  - category  (string)   ID de categoría para filtrar
 *  - featured  (bool)     Filtra productos destacados
 *  - min_price (numeric)  Precio mínimo
 *  - max_price (numeric)  Precio máximo
 *  - page      (int)      Número de página
 *  - limit     (int)      Cantidad por página
 */
class ProductIndexRequest extends FormRequest
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
            'search' => ['sometimes', 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:255'],
            'featured' => ['sometimes', 'boolean'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
