<?php

namespace App\Models;

use App\Enums\OdcSplitterRatio;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OdcSplitter extends Model
{
    protected $fillable = [
        'odc_id',
        'label',
        'input_cable_id',
        'input_core_number',
        'split_ratio',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'input_core_number' => 'integer',
            'split_ratio' => OdcSplitterRatio::class,
            'sort_order' => 'integer',
        ];
    }

    public function odc(): BelongsTo
    {
        return $this->belongsTo(Odc::class);
    }

    public function inputCable(): BelongsTo
    {
        return $this->belongsTo(FiberCable::class, 'input_cable_id');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(OdcSplitterOutput::class)->orderBy('output_port');
    }
}
