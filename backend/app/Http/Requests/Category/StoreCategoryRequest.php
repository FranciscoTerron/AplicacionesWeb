<?php

namespace App\Http\Requests\Category;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => filter_var($this->active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'slug' => Str::slug($this->name),
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
                    $existing = $fs->query('categories', ['name' => strtolower($value)], 1);
                    if (count($existing) > 0) {
                        $fail('Ya existe una categoría con ese nombre.');
                    }
                },
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('categories', ['slug' => $value], 1);
                    if (count($existing) > 0) {
                        $fail('Ya existe una categoría con ese slug.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
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
            'active.boolean' => 'El estado activo debe ser verdadero o falso.',
            'image.url.url' => 'La imagen debe ser una URL válida.',
            'image.url.required_with' => 'La imagen está incompleta (falta URL).',
            'image.public_id.required_with' => 'La imagen está incompleta (falta identificador).',
        ];
    }
}
