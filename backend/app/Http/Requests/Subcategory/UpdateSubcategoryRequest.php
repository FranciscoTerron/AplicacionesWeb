<?php

namespace App\Http\Requests\Subcategory;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateSubcategoryRequest extends FormRequest
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
        $subcategoryId = $this->route('subcategory'); // ID de la subcategoría en actualización

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($subcategoryId) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('subcategories', ['name' => strtolower($value)], 1);
                    // Si existe otra subcategoría con mismo nombre y no es esta misma → error
                    if (count($existing) > 0 && ($existing[0]['id'] ?? '') !== $subcategoryId) {
                        $fail('Ya existe una subcategoría con ese nombre.');
                    }
                },
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($subcategoryId) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('subcategories', ['slug' => $value], 1);
                    // Si existe otra subcategoría con mismo slug y no es esta misma → error
                    if (count($existing) > 0 && ($existing[0]['id'] ?? '') !== $subcategoryId) {
                        $fail('Ya existe una subcategoría con ese slug.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|string',
            'active' => 'nullable|boolean',
            'image' => 'nullable|array',
            'image.url' => 'required_with:image|string|url',
            'image.public_id' => 'required_with:image|string',
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
            'image.url.url' => 'La imagen debe ser una URL válida.',
            'image.url.required_with' => 'La imagen está incompleta (falta URL).',
            'image.public_id.required_with' => 'La imagen está incompleta (falta identificador).',
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
