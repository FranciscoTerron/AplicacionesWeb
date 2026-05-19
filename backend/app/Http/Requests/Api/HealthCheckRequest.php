<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class HealthCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * El health check no requiere autenticacion; es publico.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * El health check no recibe parametros por query o body.
     */
    public function rules(): array
    {
        return [];
    }
}
