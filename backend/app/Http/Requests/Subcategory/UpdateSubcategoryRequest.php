<?php

namespace App\Http\Requests\Subcategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255|unique:subcategories,name',
            'description' => 'nullable|string|max:500',
            'categoryId' => 'sometimes|required|string',
            'active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'name.unique' => 'Ya existe una subcategoría con ese nombre.',
            'description.max' => 'La descripción no puede tener más de 500 caracteres.',
            'categoryId.required' => 'La categoría es obligatoria.',
        ];
    }
}
