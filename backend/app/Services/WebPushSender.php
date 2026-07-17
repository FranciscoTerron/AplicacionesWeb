<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envío de notificaciones Web Push a las suscripciones guardadas (HU-B13).
 *
 * Las suscripciones son anónimas (broadcast de promos/productos nuevos) y
 * viven en la colección `push_subscriptions`, con el hash del endpoint como
 * id de documento para que suscribirse dos veces no duplique.
 */
class WebPushSender
{
    public const COLLECTION = 'push_subscriptions';

    public function __construct(private readonly FirestoreService $firestore) {}

    public static function subscriptionId(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Envía la notificación a todas las suscripciones. Las que el push
     * service reporta como muertas (404/410) se eliminan de Firestore.
     *
     * @return array{sent: int, failed: int, removed: int}
     */
    public function broadcast(string $title, string $body, string $url = '/'): array
    {
        $subscriptions = $this->firestore->listDocuments(self::COLLECTION, 500, null, 'created_at')['documents'];

        if ($subscriptions === []) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], [
            // Sin esto FCM difiere la entrega con el navegador cerrado (Doze) y
            // descarta el mensaje al vencer el TTL default de 5 minutos.
            'TTL' => 86400,
            'urgency' => 'high',
        ]);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ], JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $doc) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $doc['endpoint'],
                'keys' => [
                    'p256dh' => $doc['p256dh'],
                    'auth' => $doc['auth'],
                ],
            ]), $payload ?: '{}');
        }

        $result = ['sent' => 0, 'failed' => 0, 'removed' => 0];

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $result['sent']++;

                continue;
            }

            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSubscriptionExpired()) {
                $this->firestore->deleteDocument(self::COLLECTION, self::subscriptionId($endpoint));
                $result['removed']++;
            } else {
                Log::warning('Web push falló', ['endpoint' => $endpoint, 'reason' => $report->getReason()]);
                $result['failed']++;
            }
        }

        return $result;
    }
}
