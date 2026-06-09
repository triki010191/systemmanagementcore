@props(['hops' => [], 'metrics' => []])

@php
    $iconFor = fn (string $type) => match ($type) {
        'customer' => 'person',
        'pop' => 'cloud',
        'olt' => 'router',
        'otb' => 'account_tree',
        'odc' => 'storage',
        'odp' => 'settings_input_component',
        default => 'circle',
    };
@endphp

<section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg md:p-xl">
    <h4 class="text-label-caps text-on-surface-variant mb-lg">{{ __('hfnms.path_diagram') }}</h4>

    <div class="relative max-w-3xl mx-auto">
        @foreach ($hops as $index => $hop)
            @php
                $isFirst = $index === 0;
                $isLast = $index === count($hops) - 1;
                $isCore = $hop['type'] === 'pop' || $hop['type'] === 'customer';
            @endphp

            @if (! $isFirst)
                <div class="flex justify-center py-xs">
                    <div class="flex flex-col items-center text-primary/70">
                        <span class="w-0.5 h-6 bg-primary/30"></span>
                        <span class="material-symbols-outlined text-[18px]">south</span>
                        <span class="w-0.5 h-6 bg-primary/30"></span>
                    </div>
                </div>
            @endif

            <div class="flex gap-md items-start rounded-lg border {{ $isCore ? 'border-primary/30 bg-primary/5' : 'border-outline-variant bg-surface-container-low' }} p-md shadow-sm">
                <div class="shrink-0 w-11 h-11 rounded-lg {{ $isCore ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container-highest text-on-surface-variant border border-outline-variant' }} flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">{{ $iconFor($hop['type']) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-sm">
                        <span class="text-label-caps text-on-surface-variant">{{ $hop['type_label'] }}</span>
                        <span class="font-mono text-body-sm font-semibold text-primary">{{ $hop['code'] }}</span>
                    </div>
                    <p class="text-body-sm text-on-surface mt-xs">{{ $hop['name'] }}</p>
                    <div class="flex flex-wrap gap-sm mt-sm text-[11px] text-on-surface-variant">
                        @if (! empty($hop['port']))
                            <span class="px-sm py-xs rounded bg-surface-container-high">{{ __('hfnms.port') }}: {{ $hop['port'] }}</span>
                        @endif
                        @if (! empty($hop['odp_port']))
                            <span class="px-sm py-xs rounded bg-surface-container-high">ODP Port: {{ $hop['odp_port'] }}</span>
                        @endif
                        @if (! empty($hop['core']))
                            <span class="px-sm py-xs rounded bg-blue-50 text-blue-800 font-mono">
                                Core {{ $hop['core']['number'] }} · Tube {{ $hop['core']['tube'] }}
                                @if (! empty($hop['core']['loss_db']))
                                    · {{ $hop['core']['loss_db'] }} dB
                                @endif
                            </span>
                        @endif
                        @if (! empty($hop['drop_length_m']))
                            <span class="px-sm py-xs rounded bg-surface-container-high">{{ __('hfnms.drop_length') }}: {{ $hop['drop_length_m'] }} m</span>
                        @endif
                        @if (! empty($hop['gps']['latitude']))
                            <span class="px-sm py-xs rounded bg-surface-container-high font-mono">
                                {{ $hop['gps']['latitude'] }}, {{ $hop['gps']['longitude'] }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="shrink-0 text-right">
                    <span class="inline-flex items-center px-sm py-xs rounded text-[10px] font-bold uppercase {{ $hop['status'] === 'active' ? 'bg-green-50 text-success' : 'bg-surface-container-high text-on-surface-variant' }}">
                        {{ $hop['status'] }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    @if (! empty($metrics))
        <div class="mt-xl pt-lg border-t border-outline-variant flex flex-wrap gap-lg justify-center text-body-sm">
            @if (! empty($metrics['rx_power_dbm']))
                <div class="flex items-center gap-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                    RX: {{ $metrics['rx_power_dbm'] }} dBm
                </div>
            @endif
            @if (! empty($metrics['tx_power_dbm']))
                <div class="font-semibold">TX: {{ $metrics['tx_power_dbm'] }} dBm</div>
            @endif
            @if (! empty($metrics['distance_km']))
                <div class="font-semibold">{{ __('hfnms.distance') }}: {{ $metrics['distance_km'] }} km</div>
            @endif
        </div>
    @endif
</section>
