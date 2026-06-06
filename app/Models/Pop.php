<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pop extends Model
{
    protected $fillable = [
        'network_node_id',
        'upstream_provider',
        'router_model',
        'monitoring_enabled',
    ];

    protected function casts(): array
    {
        return [
            'monitoring_enabled' => 'boolean',
        ];
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function olts(): HasMany
    {
        return $this->hasMany(Olt::class);
    }
}
