@props(['title', 'points' => [], 'color' => '#004cca'])

@php
    $values = collect($points)->pluck('value')->all();
    $max = max(1, ...($values ?: [1]));
    $count = count($points);
    $totalIncidents = array_sum($values);
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-outline-variant/60 rounded-[12px] p-4 shadow-sm h-full']) }}>
    <div class="flex items-center justify-between gap-2 mb-3">
        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant">{{ $title }}</h3>
        @if ($count > 0)
            <span class="text-[11px] font-mono font-semibold text-on-surface-variant">{{ $totalIncidents }} {{ __('hfnms.total') }}</span>
        @endif
    </div>

    @if ($count === 0)
        <p class="text-body-sm text-on-surface-variant text-center py-8">{{ __('hfnms.no_chart_data') }}</p>
    @else
        <div class="flex items-end gap-1.5 h-[100px]">
            @foreach ($points as $point)
                @php
                    $value = $point['value'] ?? 0;
                    $height = max(6, round(($value / $max) * 100));
                    $barColor = $value >= 3 ? '#ef4444' : ($value >= 1 ? '#f59e0b' : $color);
                @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full group">
                    <span class="text-[10px] font-mono font-semibold text-on-surface mb-1 opacity-0 group-hover:opacity-100 transition-opacity">{{ $value }}</span>
                    <div
                        class="w-full max-w-[28px] rounded-t-[4px] transition-all duration-300"
                        style="height: {{ $height }}%; background: {{ $barColor }}; min-height: {{ $value > 0 ? '6px' : '2px' }}"
                    ></div>
                    <span class="text-[9px] text-on-surface-variant mt-1 truncate w-full text-center">{{ $point['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
