<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    protected $fillable = [
        'network_node_id',
        'code',
        'name',
        'phone',
        'email',
        'address',
        'service_type',
        'vlan_tag',
        'ip_address',
        'onu_serial',
        'rx_power_dbm',
        'tx_power_dbm',
        'status',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'vlan_tag' => 'integer',
            'rx_power_dbm' => 'decimal:2',
            'tx_power_dbm' => 'decimal:2',
            'registered_at' => 'date',
        ];
    }

    public function networkNode(): BelongsTo
    {
        return $this->belongsTo(NetworkNode::class);
    }

    public function connection(): HasOne
    {
        return $this->hasOne(CustomerConnection::class);
    }
}
