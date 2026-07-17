<?php

namespace App\Support;

/**
 * Vocabulario único de estados de orden y de pago (HU-B05).
 *
 * Panel, API, webhook y tienda usan estas constantes. Los documentos viejos
 * de Firestore pueden traer estados legacy (in_process, completed, paid,
 * overdue, camelCase paymentStatus): siempre leerlos vía normalize()/
 * normalizePayment() en lugar de comparar contra el valor crudo.
 */
final class OrderStatus
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const PROCESSING = 'processing';

    public const SHIPPED = 'shipped';

    public const DELIVERED = 'delivered';

    public const CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_APPROVED = 'approved';

    public const PAYMENT_REJECTED = 'rejected';

    public const PAYMENT_REFUNDED = 'refunded';

    /**
     * Estados en los que el negocio ya aceptó la orden (comprometen stock
     * para métodos de pago sin acreditación online).
     *
     * @var list<string>
     */
    public const COMMITTED_STATUSES = [
        self::CONFIRMED,
        self::PROCESSING,
        self::SHIPPED,
        self::DELIVERED,
    ];

    /**
     * @return array<string, string> value => label
     */
    public static function statuses(): array
    {
        return [
            self::PENDING => 'Pendiente',
            self::CONFIRMED => 'Confirmada',
            self::PROCESSING => 'En preparación',
            self::SHIPPED => 'Enviada',
            self::DELIVERED => 'Entregada',
            self::CANCELLED => 'Cancelada',
        ];
    }

    /**
     * @return array<string, string> value => label
     */
    public static function paymentStatuses(): array
    {
        return [
            self::PAYMENT_PENDING => 'Pendiente',
            self::PAYMENT_APPROVED => 'Aprobado',
            self::PAYMENT_REJECTED => 'Rechazado',
            self::PAYMENT_REFUNDED => 'Reembolsado',
        ];
    }

    /**
     * Mapea estados de orden legacy al vocabulario unificado.
     */
    public static function normalize(string $status): string
    {
        return [
            'in_process' => self::PROCESSING,
            'completed' => self::DELIVERED,
        ][$status] ?? $status;
    }

    /**
     * Mapea estados de pago legacy al vocabulario unificado.
     */
    public static function normalizePayment(string $status): string
    {
        return [
            'paid' => self::PAYMENT_APPROVED,
            'completed' => self::PAYMENT_APPROVED,
            // "overdue" era "venció sin pagar": el pago nunca ocurrió.
            'overdue' => self::PAYMENT_PENDING,
            'failed' => self::PAYMENT_REJECTED,
        ][$status] ?? $status;
    }

    /**
     * Estado de pago de una orden cruda de Firestore (cubre el campo legacy
     * camelCase `paymentStatus`), ya normalizado.
     *
     * @param  array<string, mixed>  $order
     */
    public static function paymentOf(array $order): string
    {
        return self::normalizePayment((string) ($order['payment_status'] ?? $order['paymentStatus'] ?? self::PAYMENT_PENDING));
    }

    /**
     * Estado de una orden cruda de Firestore, ya normalizado.
     *
     * @param  array<string, mixed>  $order
     */
    public static function of(array $order): string
    {
        return self::normalize((string) ($order['status'] ?? self::PENDING));
    }
}
