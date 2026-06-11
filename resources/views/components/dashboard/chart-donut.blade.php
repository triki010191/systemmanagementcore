@props(['title', 'segments' => [], 'total' => 0])

@php
    $total = $total > 0 ? $total : collect($segments)->sum('value');
    $active = collect($segments)->filter(fn ($s) => ($s['value'] ?? 0) > 0)->values();

    $radius = 54;
    $circumference = 2 * M_PI * $radius;
    $offset = 0;
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-outline-variant/60 rounded-[12px] p-4 shadow-sm h-full']) }}>
    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant mb-3">{{ $title }}</h3>

    @if ($total <= 0)
        <p class="text-body-sm text-on-surface-variant text-center py-8">{{ __('hfnms.no_chart_data') }}</p>
    @else
        <div class="flex flex-col sm:flex-row items-center gap-5">
            <div class="relative w-[140px] h-[140px] shrink-0">
                <svg viewBox="0 0 140 140" class="w-full h-full -rotate-90">
                    <circle cx="70" cy="70" r="{{ $radius }}" fill="none" stroke="#eef2f7" stroke-width="16" />
                    @foreach ($active as $segment)
                        @php
                            $length = ($segment['value'] / $total) * $circumference;
                            $dashArray = $length.' '.($circumference - $length);
                        @endphp
                        <circle
                            cx="70" cy="70" r="{{ $radius }}"
                            fill="none"
                            stroke="{{ $segment['color'] ?? '#94a3b8' }}"
                            stroke-width="16"
                            stroke-dasharray="{{ $dashArray }}"
                            stroke-dashoffset="{{ -$offset }}"
                        />
                        @php $offset += $length; @endphp
                    @endforeach
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-bold text-on-surface leading-none">{{ number_format($total) }}</span>
                    <span class="text-[9px] text-on-surface-variant uppercase font-semibold tracking-wide mt-0.5">{{ __('hfnms.total') }}</span>
                </div>
            </div>
            <ul class="flex-1 w-full space-y-2">
                @foreach ($active as $segment)
                    <li class="flex items-center justify-between gap-2 text-body-sm">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $segment['color'] }}"></span>
                            <span class="truncate text-on-surface-variant">{{ $segment['label'] }}</span>
                        </div>
                        <span class="font-mono text-xs font-semibold shrink-0 text-on-surface">
                            {{ number_format($segment['value']) }}
                            <span class="text-on-surface-variant">({{ round(($segment['value'] / $total) * 100) }}%)</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
