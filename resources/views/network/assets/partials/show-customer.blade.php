<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Code</dt><dd class="font-mono">{{ $asset->code }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Phone</dt><dd>{{ $asset->phone ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Email</dt><dd>{{ $asset->email ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">ONU</dt><dd class="font-mono">{{ $asset->onu_serial ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Service</dt><dd class="capitalize">{{ $asset->service_type }} / {{ $asset->status }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">RX / TX</dt><dd class="font-mono">{{ $asset->rx_power_dbm ?? '—' }} / {{ $asset->tx_power_dbm ?? '—' }} dBm</dd></div>
</dl>
