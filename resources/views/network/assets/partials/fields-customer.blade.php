<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $asset?->phone) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Email</label>
        <input type="email" name="email" value="{{ old('email', $asset?->email) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Service Type</label>
        <select name="service_type" class="w-full rounded-lg border-outline-variant text-body-sm">
            <option value="home" @selected(old('service_type', $asset?->service_type ?? 'home') === 'home')>Home</option>
            <option value="business" @selected(old('service_type', $asset?->service_type) === 'business')>Business</option>
        </select>
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Customer Status</label>
        <select name="customer_status" class="w-full rounded-lg border-outline-variant text-body-sm">
            @foreach (['active', 'suspended', 'terminated'] as $st)
                <option value="{{ $st }}" @selected(old('customer_status', $asset?->status ?? 'active') === $st)>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">ONU Serial</label>
        <input type="text" name="onu_serial" value="{{ old('onu_serial', $asset?->onu_serial) }}" class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">VLAN Tag</label>
        <input type="number" name="vlan_tag" value="{{ old('vlan_tag', $asset?->vlan_tag) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">IP Address</label>
        <input type="text" name="ip_address" value="{{ old('ip_address', $asset?->ip_address) }}" class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Registered At</label>
        <input type="date" name="registered_at" value="{{ old('registered_at', $asset?->registered_at?->format('Y-m-d')) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">RX Power (dBm)</label>
        <input type="number" step="0.01" name="rx_power_dbm" value="{{ old('rx_power_dbm', $asset?->rx_power_dbm) }}" class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">TX Power (dBm)</label>
        <input type="number" step="0.01" name="tx_power_dbm" value="{{ old('tx_power_dbm', $asset?->tx_power_dbm) }}" class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
    </div>
</div>
