<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $discount_type
 * @property float $value
 * @property int|null $max_uses
 * @property int $used_count
 * @property bool $active
 * @property string $created_by
 * @property string $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Discount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'value',
        'max_uses',
        'used_count',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;
}
