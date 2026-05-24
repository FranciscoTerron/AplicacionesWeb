<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string|null $description
 * @property string $category_id
 * @property string|null $subcategory_id
 * @property string $sku
 * @property float $price
 * @property float|null $cost
 * @property int $stock
 * @property int $min_stock
 * @property array<int,array{url:string,public_id:string}>|null $images
 * @property bool $active
 * @property bool $featured
 * @property array|null $dimensions
 * @property string $created_by
 * @property string $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'subcategory_id',
        'sku',
        'price',
        'cost',
        'stock',
        'min_stock',
        'images',
        'active',
        'featured',
        'dimensions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'active' => 'boolean',
        'featured' => 'boolean',
        'images' => 'array',
        'dimensions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;
}
