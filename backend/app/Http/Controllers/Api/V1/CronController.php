<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Tareas programadas disparadas por Vercel Cron.
 *
 * Vercel envía un header `Authorization: Bearer <CRON_SECRET>` cuando la env
 * var CRON_SECRET está configurada. Si no coincide (o no está seteada), se
 * rechaza: el endpoint queda deshabilitado por defecto.
 */
class CronController extends Controller
{
    /** Horas que una orden puede quedar sin pagar antes de expirar. */
    private const EXPIRE_AFTER_HOURS = 48;

    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * GET /api/v1/cron/expire-orders
     *
     * Cancela las órdenes que quedaron pendientes y sin pago más de 48h.
     * No borra: marca status=cancelled (reversible, auditable).
     */
    public function expireOrders(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 401);
        }

        $cutoff = Carbon::now()->subHours(self::EXPIRE_AFTER_HOURS);
        $orders = $this->firestore->listDocuments('orders', 500, null, 'created_at')['documents'] ?? [];

        $expired = 0;
        foreach ($orders as $order) {
            $isPending = ($order['status'] ?? '') === 'pending'
                && ($order['payment_status'] ?? '') === 'pending';
            $createdAt = $order['created_at'] ?? null;

            if (! $isPending || ! $createdAt) {
                continue;
            }

            if (Carbon::parse($createdAt)->lt($cutoff)) {
                $this->firestore->updateDocument('orders', (string) $order['id'], [
                    'status' => 'cancelled',
                    'cancel_reason' => 'expired_unpaid',
                    'updated_at' => now()->toISOString(),
                ]);
                $expired++;
            }
        }

        return ApiResponse::success(
            data: ['expired' => $expired],
            message: "Órdenes expiradas: {$expired}"
        );
    }

    private function authorized(Request $request): bool
    {
        $secret = (string) config('services.cron.secret');
        if ($secret === '') {
            return false;
        }

        return hash_equals($secret, (string) $request->bearerToken());
    }
}
