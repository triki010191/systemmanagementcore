<?php

namespace App\Models;

use App\Enums\CoreStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiberCore extends Model
{
    protected $fillable = [
        'cable_id',
        'cable_tube_id',
        'core_number',
        'tube_number',
        'core_position_in_tube',
        'tube_color_id',
        'status',
        'source_node_id',
        'target_node_id',
        'color_name',
        'loss_db',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'core_number' => 'integer',
            'tube_number' => 'integer',
            'core_position_in_tube' => 'integer',
            'status' => CoreStatus::class,
            'loss_db' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function cableTube(): BelongsTo
    {
        return $this->belongsTo(CableTube::class, 'cable_tube_id');
    }

    public function cable(): BelongsTo
    {
        return $this->belongsTo(FiberCable::class, 'cable_id');
    }

    public function tubeColor(): BelongsTo
    {
        return $this->belongsTo(TubeColor::class);
    }

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'target_node_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(NetworkLink::class, 'fiber_core_id');
    }

    public function otdrRecords(): HasMany
    {
        return $this->hasMany(OtdrRecord::class, 'cable_core_id');
    }
}
