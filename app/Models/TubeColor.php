<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TubeColor extends Model
{
    protected $fillable = [
        'standard',
        'tube_number',
        'color_name',
        'hex_code',
        'tracer_pattern',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tube_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function fiberCores(): HasMany
    {
        return $this->hasMany(FiberCore::class);
    }

    public function networkLinks(): HasMany
    {
        return $this->hasMany(NetworkLink::class);
    }
}
