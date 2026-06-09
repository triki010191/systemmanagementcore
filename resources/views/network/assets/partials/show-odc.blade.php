<dl class="space-y-sm text-body-sm">
    <div class="flex justify-between"><dt class="text-on-surface-variant">Domain Code</dt><dd class="font-mono">{{ $asset->code }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.port_capacity') }}</dt><dd>{{ $asset->port_used }} / {{ $asset->port_capacity }}</dd></div>
    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.split_ratio_default') }}</dt><dd>{{ $asset->split_ratio_default ?? '—' }}</dd></div>
</dl>

@if ($asset->splitters?->isNotEmpty())
    <div class="mt-md pt-md border-t border-outline-variant">
        <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.odc_dist_title') }}</p>
        <div class="space-y-sm">
            @foreach ($asset->splitters as $splitter)
                <div class="rounded-lg border border-outline-variant/60 p-sm text-body-sm">
                    <p class="font-semibold">
                        {{ $splitter->label ?: __('hfnms.odc_dist_splitter').' #'.$loop->iteration }}
                        <span class="text-on-surface-variant font-normal">· {{ $splitter->split_ratio->value }}</span>
                    </p>
                    <p class="text-on-surface-variant text-[12px] mt-xs">
                        {{ __('hfnms.odc_dist_input_cable') }}:
                        <span class="font-mono text-on-surface">{{ $splitter->inputCable?->code ?? '—' }}</span>
                        · {{ __('hfnms.odc_dist_input_core') }} <span class="font-mono text-on-surface">{{ $splitter->input_core_number }}</span>
                    </p>
                    @if ($splitter->outputs->whereNotNull('target_node_id')->isNotEmpty())
                        <ul class="mt-xs space-y-xs text-[12px]">
                            @foreach ($splitter->outputs->whereNotNull('target_node_id') as $output)
                                <li class="flex flex-wrap gap-xs">
                                    <span class="font-mono font-semibold">P{{ $output->output_port }}</span>
                                    <span>→ {{ $output->target_type?->label() }} {{ $output->targetNode?->code }}</span>
                                    @if ($output->outboundCable)
                                        <span class="text-on-surface-variant">· {{ $output->outboundCable->code }} core {{ $output->outbound_core_number }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
