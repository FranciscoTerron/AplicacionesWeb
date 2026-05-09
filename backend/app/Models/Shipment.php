<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $order_id
 * @property string $address
 * @property string|null $tracking_code
 * @property string|null $carrier
 * @property string $status
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property bool $active
 * @property string|null $notes
 * @property string $created_by
 * @property string $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'address',
        'tracking_code',
        'carrier',
        'status',
        'shipped_at',
        'delivered_at',
        'active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public static function statuses(): array
    {
        return [
            self::STATUS_PREPARING => 'En preparación',
            self::STATUS_IN_TRANSIT => 'En tránsito',
            self::STATUS_DELIVERED => 'Entregado',
            self::STATUS_CANCELLED => 'Cancelado',
        ];
    }
}
