<div class="grid grid-cols-1 md:grid-cols-3 gap-md">
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.port_capacity') }}</label>
        <input type="number" name="port_capacity" value="{{ old('port_capacity', $asset?->port_capacity ?? 0) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.port_used') }}</label>
        <input type="number" name="port_used" value="{{ old('port_used', $asset?->port_used ?? 0) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.split_ratio_default') }}</label>
        <input type="text" name="split_ratio_default" value="{{ old('split_ratio_default', $asset?->split_ratio_default) }}" placeholder="1:8" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
</div>
