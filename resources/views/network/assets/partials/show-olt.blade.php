<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Brand / Model</dt><dd>{{ $asset->brand }} {{ $asset->model }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Slots / PON</dt><dd>{{ $asset->slot_count ?? '—' }} / {{ $asset->pon_port_count ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">IP</dt><dd class="font-mono">{{ $asset->ip_address ?? '—' }}</dd></div>
</dl>
