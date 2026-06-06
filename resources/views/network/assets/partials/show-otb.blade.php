<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Domain Code</dt><dd class="font-mono">{{ $asset->code }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Trays</dt><dd>{{ $asset->tray_count ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Capacity</dt><dd>{{ $asset->capacity_cores ?? '—' }} core</dd></div>
</dl>
