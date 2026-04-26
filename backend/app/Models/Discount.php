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
 * @property Carbon $valid_from
 * @property Carbon $valid_to
 * @property bool $active
 * @property string $applies_to
 * @property array|null $applicable_ids
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
        'valid_from',
        'valid_to',
        'active',
        'applies_to',
        'applicable_ids',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'applicable_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;
}
