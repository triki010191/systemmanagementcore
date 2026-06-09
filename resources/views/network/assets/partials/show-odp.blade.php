<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Domain Code</dt><dd class="font-mono">{{ $asset->code }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.parent') }} (ODC)</dt><dd class="font-mono">{{ $asset->odc?->networkNode?->code ?? $asset->networkNode?->parent?->code ?? '—' }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.cores_from_odc') }}</dt><dd>{{ $asset->cores_from_odc }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.port_capacity') }}</dt><dd>{{ $asset->port_used }} / {{ $asset->port_capacity }}</dd></div>
</dl>
