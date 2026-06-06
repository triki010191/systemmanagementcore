<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Upstream</dt><dd>{{ $asset->upstream_provider ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Router</dt><dd>{{ $asset->router_model ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">Monitoring</dt><dd>{{ $asset->monitoring_enabled ? 'Yes' : 'No' }}</dd></div>
</dl>
