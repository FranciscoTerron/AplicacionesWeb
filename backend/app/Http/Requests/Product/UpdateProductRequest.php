<?php

namespace App\Http\Requests\Product;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product'); // ID del producto en actualización

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|string',
            'subcategory_id' => 'nullable|string',
            'sku' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($productId) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('products', ['sku' => strtolower($value)], 1);
                    // Si existe otro con mismo SKU y no es este producto → error
                    if (count($existing) > 0 && ($existing[0]['id'] ?? '') !== $productId) {
                        $fail('Ya existe un producto con ese SKU.');
                    }
                },
            ],
            'price' => ['required', 'numeric', 'min:0', function ($attribute, $value, $fail) {
                $cost = $this->input('cost');
                if ($cost !== null && $value < $cost) {
                    $fail('El precio no puede ser menor al costo.');
                }
            }],
            'cost' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'array',
            'images.*.url' => 'required|string|url',
            'images.*.public_id' => 'required|string',
            'active' => 'boolean',
            'featured' => 'boolean',
            'dimensions' => 'nullable|array',
            'dimensions.weight_kg' => 'nullable|numeric|min:0',
            'dimensions.length_cm' => 'nullable|numeric|min:0',
            'dimensions.width_cm' => 'nullable|numeric|min:0',
            'dimensions.height_cm' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'description.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'category_id.required' => 'La categoría es obligatoria.',
            'sku.required' => 'El SKU es obligatorio.',
            'sku.max' => 'El SKU no puede tener más de 100 caracteres.',
            'price.required' => 'El precio es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'min_stock.required' => 'El stock mínimo es obligatorio.',
            'images.*.url.url' => 'Cada imagen debe ser una URL válida.',
            'images.*.url.required' => 'Cada imagen debe tener URL.',
            'images.*.public_id.required' => 'Cada imagen debe tener identificador.',
        ];
    }

    /**
     * Validación de existencia de category_id (activa) y subcategory_id.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $categoryId = $this->input('category_id');
            if ($categoryId) {
                $category = app(FirestoreService::class)->getDocument('categories', $categoryId);
                if (! $category || ! ($category['active'] ?? false)) {
                    $validator->errors()->add('category_id', 'La categoría seleccionada no existe o está inactiva.');
                }
            }

            $subcategoryId = $this->input('subcategory_id');
            if ($subcategoryId) {
                $subcategory = app(FirestoreService::class)->getDocument('subcategories', $subcategoryId);
                if (! $subcategory) {
                    $validator->errors()->add('subcategory_id', 'La subcategoría seleccionada no existe.');
                } elseif ($categoryId && ($subcategory['category_id'] ?? null) !== $categoryId) {
                    $validator->errors()->add('subcategory_id', 'La subcategoría no pertenece a la categoría seleccionada.');
                }
            }
        });
    }
}
