<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerConnection extends Model
{
    protected $fillable = [
        'customer_id',
        'odp_id',
        'cable_core_id',
        'odp_port_number',
        'drop_length_m',
        'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'odp_port_number' => 'integer',
            'drop_length_m' => 'decimal:2',
            'connected_at' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function odp(): BelongsTo
    {
        return $this->belongsTo(Odp::class);
    }

    public function cableCore(): BelongsTo
    {
        return $this->belongsTo(FiberCore::class, 'cable_core_id');
    }
}
