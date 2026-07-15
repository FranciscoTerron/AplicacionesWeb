<?php

namespace App\Http\Requests\Api\Push;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/push/subscribe
 *
 * Guarda una suscripción Web Push anónima (formato PushSubscription.toJSON()
 * del navegador).
 */
class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'url', 'starts_with:https://', 'max:2000'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ];
    }
}
