<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $store_name
 * @property string $contact_email
 * @property string|null $description
 * @property string|null $created_by
 * @property string|null $updated_by
 */
class Setting extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['store_name', 'contact_email', 'description', 'created_by', 'updated_by'];
}
