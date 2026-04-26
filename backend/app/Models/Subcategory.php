<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $category_id
 * @property string|null $description
 * @property bool $active
 * @property string|null $image
 * @property string $created_by
 * @property string $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Subcategory extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'description',
        'active',
        'image',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    // Relación (opcional, para futuro uso)
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
