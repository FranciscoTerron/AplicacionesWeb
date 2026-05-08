<?php

namespace App\Http\Requests\Subcategory;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Generate slug from name
        $this->merge([
            'slug' => Str::slug($this->name),
            'active' => filter_var($this->active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('subcategories', ['name' => strtolower($value)], 1);
                    if (count($existing) > 0) {
                        $fail('Ya existe una subcategoría con ese nombre.');
                    }
                },
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('subcategories', ['slug' => $value], 1);
                    if (count($existing) > 0) {
                        $fail('Ya existe una subcategoría con ese slug.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|string',
            'active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'slug.required' => 'El slug es obligatorio.',
            'slug.max' => 'El slug no puede tener más de 255 caracteres.',
            'description.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'category_id.required' => 'La categoría es obligatoria.',
            'active.boolean' => 'El estado activo debe ser verdadero o falso.',
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
