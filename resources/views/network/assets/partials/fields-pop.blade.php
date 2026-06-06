<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Upstream Provider</label>
        <input type="text" name="upstream_provider" value="{{ old('upstream_provider', $asset?->upstream_provider) }}"
               class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Router Model</label>
        <input type="text" name="router_model" value="{{ old('router_model', $asset?->router_model) }}"
               class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary text-body-sm">
    </div>
</div>
<div class="flex items-center gap-sm">
    <input type="hidden" name="monitoring_enabled" value="0">
    <input type="checkbox" name="monitoring_enabled" value="1" id="monitoring_enabled"
           @checked(old('monitoring_enabled', $asset?->monitoring_enabled ?? true)) class="rounded border-outline-variant text-primary">
    <label for="monitoring_enabled" class="text-body-sm">Monitoring Enabled</label>
</div>
