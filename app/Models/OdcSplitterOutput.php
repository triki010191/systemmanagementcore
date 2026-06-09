<?php

namespace App\Models;

use App\Enums\OdcSplitterRouteMode;
use App\Enums\OdcSplitterTargetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdcSplitterOutput extends Model
{
    protected $fillable = [
        'odc_splitter_id',
        'output_port',
        'target_type',
        'target_node_id',
        'outbound_cable_id',
        'outbound_core_number',
        'route_mode',
        'downstream_cable_id',
        'downstream_core_number',
        'core_joint_id',
    ];

    protected function casts(): array
    {
        return [
            'output_port' => 'integer',
            'target_type' => OdcSplitterTargetType::class,
            'route_mode' => OdcSplitterRouteMode::class,
            'outbound_core_number' => 'integer',
            'downstream_core_number' => 'integer',
        ];
    }

    public function splitter(): BelongsTo
    {
        return $this->belongsTo(OdcSplitter::class, 'odc_splitter_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'target_node_id');
    }

    public function outboundCable(): BelongsTo
    {
        return $this->belongsTo(FiberCable::class, 'outbound_cable_id');
    }

    public function downstreamCable(): BelongsTo
    {
        return $this->belongsTo(FiberCable::class, 'downstream_cable_id');
    }

    public function coreJoint(): BelongsTo
    {
        return $this->belongsTo(CoreJoint::class);
    }
}
