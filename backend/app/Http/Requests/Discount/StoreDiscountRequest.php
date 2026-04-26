<?php

namespace App\Http\Requests;

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
            'code' => 'required|string|max:50|unique:discounts,code',
            'description' => 'nullable|string|max:500',
            'discountType' => 'required|in:percentage,fixed',
            'discountValue' => 'required|numeric|min:0',
            'minPurchase' => 'nullable|numeric|min:0',
            'maxDiscount' => 'nullable|numeric|min:0',
            'validFrom' => 'nullable|date',
            'validUntil' => 'nullable|date|after:validFrom',
            'active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.max' => 'El código no puede tener más de 50 caracteres.',
            'code.unique' => 'Ya existe un descuento con ese código.',
            'discountType.required' => 'El tipo de descuento es obligatorio.',
            'discountType.in' => 'El tipo de descuento debe ser percentage o fixed.',
            'discountValue.required' => 'El valor del descuento es obligatorio.',
            'discountValue.numeric' => 'El valor debe ser un número válido.',
            'discountValue.min' => 'El valor no puede ser negativo.',
            'minPurchase.numeric' => 'La compra mínima debe ser un número válido.',
            'minPurchase.min' => 'La compra mínima no puede ser negativa.',
            'maxDiscount.numeric' => 'El descuento máximo debe ser un número válido.',
            'maxDiscount.min' => 'El descuento máximo no puede ser negativo.',
            'validUntil.after' => 'La fecha de vigencia debe ser posterior a la fecha de inicio.',
        ];
    }
}
