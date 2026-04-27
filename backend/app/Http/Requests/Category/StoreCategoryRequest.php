<?php

namespace App\Http\Requests\Category;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                    $existing = $fs->query('categories', ['name' => strtolower($value)], 1);
                    if (count($existing) > 0) {
                        $fail('Ya existe una categoría con ese nombre.');
                    }
                },
            ],
            'description' => 'nullable|string|max:500',
            'active' => 'boolean',
            'order' => 'required|integer|min:0',
            'image' => 'nullable|string|url',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'description.max' => 'La descripción no puede tener más de 500 caracteres.',
            'order.required' => 'El orden es obligatorio.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden no puede ser negativo.',
            'image.url' => 'La imagen debe ser una URL válida.',
        ];
    }
}
