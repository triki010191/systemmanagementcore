@props(['title', 'points' => [], 'color' => '#3b82f6'])

@php
    $values = collect($points)->pluck('value')->all();
    $max = max(1, ...($values ?: [1]));
    $count = count($points);
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface-container-lowest border border-outline-variant rounded-xl p-lg']) }}>
    <h3 class="text-label-caps text-on-surface-variant mb-lg">{{ $title }}</h3>

    @if ($count === 0)
        <p class="text-body-sm text-on-surface-variant text-center py-xl">{{ __('hfnms.no_chart_data') }}</p>
    @else
        <div class="flex items-end gap-1 h-[120px]">
            @foreach ($points as $point)
                @php $height = max(4, round((($point['value'] ?? 0) / $max) * 100)); @endphp
                <div class="flex-1 flex flex-col items-center justify-end h-full group">
                    <span class="text-[10px] font-mono font-semibold text-on-surface mb-xs opacity-0 group-hover:opacity-100 transition-opacity">{{ $point['value'] ?? 0 }}</span>
                    <div class="w-full max-w-[32px] rounded-t-md transition-all duration-500 hover:opacity-80"
                         style="height: {{ $height }}%; background: {{ $color }}; min-height: {{ ($point['value'] ?? 0) > 0 ? '4px' : '2px' }}"></div>
                    <span class="text-[9px] text-on-surface-variant mt-xs truncate w-full text-center">{{ $point['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
