<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CableTube extends Model
{
    protected $fillable = [
        'cable_id',
        'tube_number',
        'tube_color_id',
        'core_count',
    ];

    protected function casts(): array
    {
        return [
            'tube_number' => 'integer',
            'core_count' => 'integer',
        ];
    }

    public function cable(): BelongsTo
    {
        return $this->belongsTo(FiberCable::class, 'cable_id');
    }

    public function tubeColor(): BelongsTo
    {
        return $this->belongsTo(TubeColor::class);
    }

    public function cores(): HasMany
    {
        return $this->hasMany(FiberCore::class, 'cable_tube_id');
    }
}
