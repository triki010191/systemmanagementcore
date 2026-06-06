<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FiberCable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'manufacturer',
        'fiber_type',
        'jacket_rating',
        'cable_type',
        'core_count',
        'tube_count',
        'length_meters',
        'installed_at',
        'qr_code_path',
        'route_geometry',
        'start_node_id',
        'end_node_id',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'core_count' => 'integer',
            'tube_count' => 'integer',
            'length_meters' => 'decimal:2',
            'installed_at' => 'date',
            'route_geometry' => 'array',
            'metadata' => 'array',
        ];
    }

    public function tubes(): HasMany
    {
        return $this->hasMany(CableTube::class, 'cable_id');
    }

    public function startNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'start_node_id');
    }

    public function endNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'end_node_id');
    }

    public function cores(): HasMany
    {
        return $this->hasMany(FiberCore::class, 'cable_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(NetworkLink::class, 'cable_id');
    }
}
