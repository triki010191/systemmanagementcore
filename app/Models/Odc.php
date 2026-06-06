<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odc extends Model
{
    protected $fillable = [
        'network_node_id',
        'code',
        'otb_id',
        'port_capacity',
        'port_used',
        'split_ratio_default',
    ];

    protected function casts(): array
    {
        return [
            'port_capacity' => 'integer',
            'port_used' => 'integer',
        ];
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function otb(): BelongsTo
    {
        return $this->belongsTo(Otb::class);
    }

    public function odps(): HasMany
    {
        return $this->hasMany(Odp::class);
    }

    public function splitters(): HasMany
    {
        return $this->hasMany(Splitter::class);
    }
}
