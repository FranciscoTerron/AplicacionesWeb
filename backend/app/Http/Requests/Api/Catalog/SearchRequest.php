<?php

namespace App\Http\Requests\Api\Catalog;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/catalog/search
 *
 * Búsqueda avanzada de productos con múltiples filtros.
 * Auth: No requerido (público)
 */
class SearchRequest extends FormRequest
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
            'query' => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'string', 'max:255'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'min_rating' => ['sometimes', 'numeric', 'min:0', 'max:5'],
            'in_stock' => ['sometimes', 'boolean'],
            'attributes' => ['sometimes', 'array'],
            'sort_by' => ['sometimes', 'string', 'in:price,name,rating,created_at'],
            'sort_order' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }
}
