<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtdrRecord extends Model
{
    protected $fillable = [
        'cable_core_id',
        'total_loss_db',
        'distance_km',
        'fault_distance_km',
        'event_points',
        'notes',
        'trace_file_path',
        'measured_by',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'total_loss_db' => 'decimal:2',
            'distance_km' => 'decimal:3',
            'fault_distance_km' => 'decimal:3',
            'event_points' => 'array',
            'measured_at' => 'datetime',
        ];
    }

    public function cableCore(): BelongsTo
    {
        return $this->belongsTo(FiberCore::class, 'cable_core_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'measured_by');
    }
}
