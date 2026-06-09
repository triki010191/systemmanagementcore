<div class="space-y-md">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        <div>
            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.port_capacity') }}</label>
            <input type="number" name="port_capacity" value="{{ old('port_capacity', $asset?->port_capacity ?? 16) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
        </div>
        <div>
            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.port_used') }}</label>
            <input type="number" name="port_used" value="{{ old('port_used', $asset?->port_used ?? 0) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
        </div>
    </div>

    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cores_from_odc') }}</label>
        <input type="number" name="cores_from_odc" min="0" max="999" readonly
               value="{{ old('cores_from_odc', $asset?->cores_from_odc ?? 0) }}"
               class="w-full md:w-1/3 rounded-lg border-outline-variant text-body-sm bg-surface-container-low">
        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.cores_from_odc_help') }}</p>
        @error('cores_from_odc') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
    </div>
</div>
