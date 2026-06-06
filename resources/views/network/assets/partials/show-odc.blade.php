<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Domain Code</dt><dd class="font-mono">{{ $asset->code }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Ports</dt><dd>{{ $asset->port_used }} / {{ $asset->port_capacity }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Split Ratio</dt><dd>{{ $asset->split_ratio_default ?? '—' }}</dd></div>
</dl>
