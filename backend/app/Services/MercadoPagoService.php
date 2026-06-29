<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integración con Mercado Pago (Checkout Pro).
 *
 * Crea una "preference" y devuelve el init_point al que se redirige al usuario.
 * Mismo patrón HTTP que PaymentWebhookController::getPayment() — sin SDK.
 *
 * El ciclo se cierra de forma asíncrona en PaymentWebhookController, que busca
 * la orden por external_reference y valida el monto.
 */
class MercadoPagoService
{
    private const PREFERENCES_URL = 'https://api.mercadopago.com/checkout/preferences';

    /**
     * Crea una preference para una orden ya persistida.
     *
     * @param  array<string, mixed>  $order  Orden (con id, items, total_amount).
     * @return array{init_point: string, preference_id: string}
     */
    public function createPreference(array $order): array
    {
        $accessToken = (string) config('services.mercadopago.access_token');
        if ($accessToken === '') {
            throw new RuntimeException('MERCADOPAGO_ACCESS_TOKEN no configurado');
        }

        $orderId = (string) ($order['id'] ?? '');
        $frontendUrl = rtrim((string) config('services.mercadopago.frontend_url'), '/');

        // Items desde los precios server-side de la orden (no del cliente).
        // El total debe coincidir EXACTO con total_amount o el webhook lo rechaza.
        $items = [];
        foreach ($order['items'] ?? [] as $item) {
            $items[] = [
                'title' => (string) ($item['name'] ?? 'Producto'),
                'quantity' => (int) ($item['quantity'] ?? 1),
                'unit_price' => (float) ($item['price'] ?? 0),
                'currency_id' => 'ARS',
            ];
        }

        $payload = [
            'items' => $items,
            'external_reference' => $orderId,
            'back_urls' => [
                'success' => "{$frontendUrl}/cuenta/ordenes/{$orderId}?pago=success",
                'failure' => "{$frontendUrl}/cuenta/ordenes/{$orderId}?pago=failure",
                'pending' => "{$frontendUrl}/cuenta/ordenes/{$orderId}?pago=pending",
            ],
        ];

        // auto_return solo con HTTPS: Mercado Pago rechaza URLs localhost/http.
        if (str_starts_with($frontendUrl, 'https://')) {
            $payload['auto_return'] = 'approved';
        }

        // NO seteamos notification_url en la preference a propósito: cuando se
        // define, MP firma la notificación con el secret de la integración API
        // (distinto del que se ve/configura en el panel "Webhooks"), y la firma
        // no valida. Sin notification_url, MP usa el webhook configurado en el
        // panel (cuya URL ya apunta al backend) y firma con ESE secret, que es
        // el que tenemos en MERCADOPAGO_WEBHOOK_SECRET. El webhook se administra
        // desde el panel de MP (su URL ya apunta al backend).

        $response = Http::withToken($accessToken)->post(self::PREFERENCES_URL, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Mercado Pago rechazó la preference: '.$response->body());
        }

        $json = $response->json();

        // Con credenciales de prueba MP devuelve sandbox_init_point; en prod, init_point.
        $initPoint = (string) ($json['init_point'] ?? $json['sandbox_init_point'] ?? '');
        $preferenceId = (string) ($json['id'] ?? '');

        if ($initPoint === '' || $preferenceId === '') {
            throw new RuntimeException('Respuesta de Mercado Pago sin init_point/id');
        }

        return [
            'init_point' => $initPoint,
            'preference_id' => $preferenceId,
        ];
    }
}
