<?php

namespace App\Models;

use App\Enums\PortStatus;
use App\Enums\SplitterPortDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SplitterPort extends Model
{
    protected $fillable = [
        'splitter_id',
        'port_number',
        'direction',
        'status',
        'label',
        'connected_core_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'port_number' => 'integer',
            'direction' => SplitterPortDirection::class,
            'status' => PortStatus::class,
            'metadata' => 'array',
        ];
    }

    public function splitter(): BelongsTo
    {
        return $this->belongsTo(Splitter::class);
    }

    public function connectedCore(): BelongsTo
    {
        return $this->belongsTo(FiberCore::class, 'connected_core_id');
    }

    public function customerConnection(): HasOne
    {
        return $this->hasOne(CustomerConnection::class, 'splitter_port_id');
    }

    public function sourceLinks(): HasMany
    {
        return $this->hasMany(NetworkLink::class, 'source_port_id');
    }

    public function targetLinks(): HasMany
    {
        return $this->hasMany(NetworkLink::class, 'target_port_id');
    }
}
