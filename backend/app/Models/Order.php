<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $client_id
 * @property string $user_id
 * @property string $status
 * @property float $total_amount
 * @property string $payment_method
 * @property string $payment_status
 * @property string $shipping_address
 * @property string $tracking_number
 * @property string $created_by
 * @property string $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Order extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'status',
        'total_amount',
        'payment_method',
        'payment_status',
        'shipping_address',
        'tracking_number',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;
}
