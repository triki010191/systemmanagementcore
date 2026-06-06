<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Port Capacity</label>
        <input type="number" name="port_capacity" value="{{ old('port_capacity', $asset?->port_capacity ?? 16) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Port Used</label>
        <input type="number" name="port_used" value="{{ old('port_used', $asset?->port_used ?? 0) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
</div>
