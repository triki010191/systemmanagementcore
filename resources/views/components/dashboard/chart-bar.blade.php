@props(['title', 'items' => [], 'max' => null])

@php
    $max = $max ?? max(1, collect($items)->max('value') ?? 1);
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface-container-lowest border border-outline-variant rounded-xl p-lg']) }}>
    <h3 class="text-label-caps text-on-surface-variant mb-lg">{{ $title }}</h3>

    @if ($items === [])
        <p class="text-body-sm text-on-surface-variant text-center py-xl">{{ __('hfnms.no_chart_data') }}</p>
    @else
        <ul class="space-y-md">
            @foreach ($items as $item)
                @php $width = $max > 0 ? round((($item['value'] ?? 0) / $max) * 100) : 0; @endphp
                <li>
                    <div class="flex justify-between text-body-sm mb-xs">
                        <span class="text-on-surface-variant truncate pr-md">{{ $item['label'] }}</span>
                        <span class="font-mono font-semibold shrink-0">{{ number_format($item['value'] ?? 0) }}</span>
                    </div>
                    <div class="h-2.5 bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700"
                             style="width: {{ max($width, ($item['value'] ?? 0) > 0 ? 4 : 0) }}%; background: {{ $item['color'] ?? '#3b82f6' }}"></div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
