<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string|null $description
 * @property bool $active
 * @property int $order
 * @property string|null $image
 * @property string $created_by
 * @property string $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
        'active',
        'order',
        'image',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;
}
