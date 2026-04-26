<?php

namespace App\Http\Requests\Discount;

use App\Services\FirestoreService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discountId = $this->route('discount'); // ID del descuento en actualización

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($discountId) {
                    $fs = app(FirestoreService::class);
                    $existing = $fs->query('discounts', ['code' => strtoupper($value)], 1);
                    // Si existe otro con mismo código y no es este descuento → error
                    if (count($existing) > 0 && ($existing[0]['id'] ?? '') !== $discountId) {
                        $fail('Ya existe un descuento con ese código.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'active' => 'boolean',
            'applies_to' => 'required|in:all,categories,products',
            'applicable_ids' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if ($this->input('applies_to') !== 'all' && empty($value)) {
                        $fail('Debes seleccionar al menos un ítem aplicable cuando applies_to no es "all".');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.max' => 'El código no puede tener más de 50 caracteres.',
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
            'valid_from.required' => 'La fecha de inicio es obligatoria.',
            'valid_from.date' => 'La fecha de inicio debe ser válida.',
            'valid_to.required' => 'La fecha de fin es obligatoria.',
            'valid_to.date' => 'La fecha de fin debe ser válida.',
            'valid_to.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'applies_to.required' => 'El campo "aplica a" es obligatorio.',
            'applies_to.in' => 'Valor no válido para "aplica a".',
        ];
    }
}
