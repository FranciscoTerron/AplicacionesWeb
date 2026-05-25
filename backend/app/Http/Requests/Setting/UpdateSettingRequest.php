<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:100'],
            'contact_email' => ['required', 'email', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_name.required' => 'El nombre de la tienda es obligatorio.',
            'store_name.max' => 'El nombre de la tienda no puede superar los 100 caracteres.',
            'contact_email.required' => 'El email de contacto es obligatorio.',
            'contact_email.email' => 'El email debe ser una dirección válida.',
            'contact_email.max' => 'El email no puede superar los 100 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
        ];
    }
}
