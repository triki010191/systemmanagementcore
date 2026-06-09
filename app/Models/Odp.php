<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odp extends Model
{
    protected $fillable = [
        'network_node_id',
        'code',
        'odc_id',
        'port_capacity',
        'port_used',
        'cores_from_odc',
    ];

    protected function casts(): array
    {
        return [
            'port_capacity' => 'integer',
            'port_used' => 'integer',
            'cores_from_odc' => 'integer',
        ];
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    public function customerConnections(): HasMany
    {
        return $this->hasMany(CustomerConnection::class);
    }
}
