<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Olt extends Model
{
    protected $fillable = [
        'network_node_id',
        'pop_id',
        'brand',
        'model',
        'slot_count',
        'pon_port_count',
        'ip_address',
        'snmp_community',
    ];

    protected function casts(): array
    {
        return [
            'slot_count' => 'integer',
            'pon_port_count' => 'integer',
        ];
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function pop(): BelongsTo
    {
        return $this->belongsTo(Pop::class);
    }

    public function otbs(): HasMany
    {
        return $this->hasMany(Otb::class);
    }
}
