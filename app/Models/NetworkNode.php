<?php

namespace App\Models;

use App\Enums\NetworkNodeStatus;
use App\Enums\NetworkNodeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkNode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'type',
        'code',
        'name',
        'description',
        'latitude',
        'longitude',
        'parent_id',
        'metadata',
        'status',
        'qr_code_path',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'type' => NetworkNodeType::class,
            'status' => NetworkNodeStatus::class,
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'metadata' => 'array',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NetworkNode::class, 'parent_id');
    }

    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(NetworkLink::class, 'source_node_id');
    }

    public function incomingLinks(): HasMany
    {
        return $this->hasMany(NetworkLink::class, 'target_node_id');
    }

    public function pop(): HasOne
    {
        return $this->hasOne(Pop::class);
    }

    public function olt(): HasOne
    {
        return $this->hasOne(Olt::class);
    }

    public function otb(): HasOne
    {
        return $this->hasOne(Otb::class);
    }

    public function odc(): HasOne
    {
        return $this->hasOne(Odc::class);
    }

    public function odp(): HasOne
    {
        return $this->hasOne(Odp::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function assetPhotos(): HasMany
    {
        return $this->hasMany(AssetPhoto::class);
    }

    public function troubleTickets(): HasMany
    {
        return $this->hasMany(TroubleTicket::class);
    }

    public function sourceFiberCores(): HasMany
    {
        return $this->hasMany(FiberCore::class, 'source_node_id');
    }

    public function targetFiberCores(): HasMany
    {
        return $this->hasMany(FiberCore::class, 'target_node_id');
    }

    public function cablesStarted(): HasMany
    {
        return $this->hasMany(FiberCable::class, 'start_node_id');
    }

    public function cablesEnded(): HasMany
    {
        return $this->hasMany(FiberCable::class, 'end_node_id');
    }
}
