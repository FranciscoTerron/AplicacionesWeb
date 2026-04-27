<?php

namespace App\Http\Requests\Subcategory;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subcategoryId = $this->route('subcategory'); // ID de la subcategoría en actualización

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($subcategoryId) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('subcategories', ['name' => strtolower($value)], 1);
                    // Si existe otro con mismo nombre y no es esta misma subcategoría → error
                    if (count($existing) > 0 && ($existing[0]['id'] ?? '') !== $subcategoryId) {
                        $fail('Ya existe una subcategoría con ese nombre.');
                    }
                },
            ],
            'description' => 'nullable|string|max:500',
            'category_id' => 'required|string',
            'active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'description.max' => 'La descripción no puede tener más de 500 caracteres.',
            'category_id.required' => 'La categoría es obligatoria.',
        ];
    }

    /**
     * Validación adicional: la categoría debe existir y estar activa.
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
        });
    }
}
