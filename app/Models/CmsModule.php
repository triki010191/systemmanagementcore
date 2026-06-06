<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsModule extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_enabled',
        'is_core',
        'config',
        'sort_order',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'is_core' => 'boolean',
            'config' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
