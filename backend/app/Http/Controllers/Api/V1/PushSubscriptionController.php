<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Push\StorePushSubscriptionRequest;
use App\Services\FirestoreService;
use App\Services\WebPushSender;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Suscripciones Web Push anónimas (HU-B13).
 *
 * El id de documento es el sha256 del endpoint: suscribirse dos veces con el
 * mismo endpoint sobreescribe en lugar de duplicar, y desuscribirse no
 * necesita conocer el id.
 */
class PushSubscriptionController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * GET /api/v1/push/public-key
     *
     * Devuelve la clave VAPID pública para que el navegador pueda suscribirse.
     */
    #[OA\Get(
        path: '/api/v1/push/public-key',
        operationId: 'getPushPublicKey',
        tags: ['Push'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Clave pública VAPID',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function publicKey(): JsonResponse
    {
        $key = config('services.webpush.public_key');

        if (! $key) {
            return ApiResponse::error(message: 'Notificaciones push no configuradas', status: 503);
        }

        return ApiResponse::success(
            data: ['public_key' => $key],
            message: 'Clave pública VAPID'
        );
    }

    /**
     * POST /api/v1/push/subscribe
     *
     * Registra la suscripción push del navegador (anónima, idempotente).
     */
    #[OA\Post(
        path: '/api/v1/push/subscribe',
        operationId: 'pushSubscribe',
        tags: ['Push'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'endpoint', type: 'string'),
                    new OA\Property(property: 'keys', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Suscripción guardada'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function subscribe(StorePushSubscriptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $docId = WebPushSender::subscriptionId($data['endpoint']);

        $this->firestore->createDocumentWithId(WebPushSender::COLLECTION, $docId, [
            'endpoint' => $data['endpoint'],
            'p256dh' => $data['keys']['p256dh'],
            'auth' => $data['keys']['auth'],
            'created_at' => now()->toISOString(),
        ]);

        return ApiResponse::success(message: 'Suscripción guardada');
    }

    /**
     * POST /api/v1/push/unsubscribe
     *
     * Elimina la suscripción asociada al endpoint.
     */
    #[OA\Post(
        path: '/api/v1/push/unsubscribe',
        operationId: 'pushUnsubscribe',
        tags: ['Push'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'endpoint', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Suscripción eliminada'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2000'],
        ]);

        $this->firestore->deleteDocument(
            WebPushSender::COLLECTION,
            WebPushSender::subscriptionId($validated['endpoint'])
        );

        return ApiResponse::success(message: 'Suscripción eliminada');
    }
}
