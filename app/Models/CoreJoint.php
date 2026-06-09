<?php

namespace App\Models;

use App\Enums\CoreJointType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreJoint extends Model
{
    protected $fillable = [
        'code',
        'joint_type',
        'source_node_id',
        'target_node_id',
        'core_number',
        'network_node_id',
        'latitude',
        'longitude',
        'odc_id',
        'odp_id',
        'fiber_core_a_id',
        'fiber_core_b_id',
        'splice_loss_db',
        'notes',
        'spliced_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joint_type' => CoreJointType::class,
            'core_number' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'splice_loss_db' => 'decimal:2',
            'spliced_at' => 'datetime',
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

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function coreA(): BelongsTo
    {
        return $this->belongsTo(FiberCore::class, 'fiber_core_a_id');
    }

    public function coreB(): BelongsTo
    {
        return $this->belongsTo(FiberCore::class, 'fiber_core_b_id');
    }
}
