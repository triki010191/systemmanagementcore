<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Brand</label>
        <input type="text" name="brand" value="{{ old('brand', $asset?->brand) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Model</label>
        <input type="text" name="model" value="{{ old('model', $asset?->model) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Slot Count</label>
        <input type="number" name="slot_count" value="{{ old('slot_count', $asset?->slot_count) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">PON Ports</label>
        <input type="number" name="pon_port_count" value="{{ old('pon_port_count', $asset?->pon_port_count) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">IP Address</label>
        <input type="text" name="ip_address" value="{{ old('ip_address', $asset?->ip_address) }}" class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">SNMP Community</label>
        <input type="text" name="snmp_community" value="{{ old('snmp_community', $asset?->snmp_community) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
</div>
