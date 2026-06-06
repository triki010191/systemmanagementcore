<?php

namespace App\Models;

use App\Enums\LinkDirection;
use App\Enums\NetworkLinkType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'source_node_id',
        'target_node_id',
        'link_type',
        'direction',
        'cable_id',
        'fiber_core_id',
        'source_port_id',
        'target_port_id',
        'core_number',
        'tube_number',
        'tube_color_id',
        'length_meters',
        'loss_db',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'link_type' => NetworkLinkType::class,
            'direction' => LinkDirection::class,
            'core_number' => 'integer',
            'tube_number' => 'integer',
            'length_meters' => 'decimal:2',
            'loss_db' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'target_node_id');
    }

    public function cable(): BelongsTo
    {
        return $this->belongsTo(FiberCable::class, 'cable_id');
    }

    public function fiberCore(): BelongsTo
    {
        return $this->belongsTo(FiberCore::class, 'fiber_core_id');
    }

    public function sourcePort(): BelongsTo
    {
        return $this->belongsTo(SplitterPort::class, 'source_port_id');
    }

    public function targetPort(): BelongsTo
    {
        return $this->belongsTo(SplitterPort::class, 'target_port_id');
    }

    public function tubeColor(): BelongsTo
    {
        return $this->belongsTo(TubeColor::class);
    }
}
