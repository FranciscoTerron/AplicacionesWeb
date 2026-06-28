<?php

namespace App\Http\Requests\Discount;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;

class StoreDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9]+$/',
                function ($attribute, $value, $fail) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('discounts', ['code' => strtoupper($value)], 1);
                    if (count($existing) > 0) {
                        $fail('Ya existe un descuento con ese código.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'active' => 'nullable|in:0,1,true,false',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.max' => 'El código no puede tener más de 50 caracteres.',
            'code.regex' => 'El código solo puede contener letras y números.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'description.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'discount_type.required' => 'El tipo de descuento es obligatorio.',
            'discount_type.in' => 'El tipo debe ser "percentage" o "fixed".',
            'value.required' => 'El valor del descuento es obligatorio.',
            'value.numeric' => 'El valor debe ser un número.',
            'value.min' => 'El valor no puede ser negativo.',
            'max_uses.integer' => 'Los usos máximos debe ser un número entero.',
            'max_uses.min' => 'Los usos máximos debe ser al menos 1.',
        ];
    }
}
