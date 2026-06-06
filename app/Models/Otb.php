<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Otb extends Model
{
    protected $fillable = [
        'network_node_id',
        'code',
        'olt_id',
        'tray_count',
        'capacity_cores',
    ];

    protected function casts(): array
    {
        return [
            'tray_count' => 'integer',
            'capacity_cores' => 'integer',
        ];
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function olt(): BelongsTo
    {
        return $this->belongsTo(Olt::class);
    }

    public function odcs(): HasMany
    {
        return $this->hasMany(Odc::class);
    }
}
