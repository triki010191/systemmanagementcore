<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Ratio</dt><dd class="font-mono">{{ $asset->ratio?->value }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Ports</dt><dd>{{ $asset->input_port_count }} IN / {{ $asset->output_port_count }} OUT</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Brand</dt><dd>{{ $asset->brand ?? '—' }}</dd></div>
</dl>
