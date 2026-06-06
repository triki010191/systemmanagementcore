<?php

namespace App\Models;

use App\Enums\SplitterRatio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Splitter extends Model
{
    protected $fillable = [
        'network_node_id',
        'odc_id',
        'ratio',
        'input_port_count',
        'output_port_count',
        'brand',
    ];

    protected function casts(): array
    {
        return [
            'ratio' => SplitterRatio::class,
            'input_port_count' => 'integer',
            'output_port_count' => 'integer',
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

    public function ports(): HasMany
    {
        return $this->hasMany(SplitterPort::class);
    }

    public function inputPorts(): HasMany
    {
        return $this->ports()->where('direction', 'input');
    }

    public function outputPorts(): HasMany
    {
        return $this->ports()->where('direction', 'output');
    }
}
