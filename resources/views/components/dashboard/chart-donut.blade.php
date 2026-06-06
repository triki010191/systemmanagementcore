@props(['title', 'segments' => [], 'total' => 0])

@php
    $total = $total > 0 ? $total : collect($segments)->sum('value');
    $gradientParts = [];
    $cursor = 0;

    foreach ($segments as $segment) {
        if ($total <= 0 || ($segment['value'] ?? 0) <= 0) {
            continue;
        }
        $percent = ($segment['value'] / $total) * 100;
        $next = $cursor + $percent;
        $gradientParts[] = ($segment['color'] ?? '#94a3b8').' '.$cursor.'% '.$next.'%';
        $cursor = $next;
    }

    if ($gradientParts === []) {
        $gradientParts[] = '#e2e8f0 0% 100%';
    }

    $gradient = 'conic-gradient('.implode(', ', $gradientParts).')';
@endphp

<div {{ $attributes->merge(['class' => 'bg-surface-container-lowest border border-outline-variant rounded-xl p-lg']) }}>
    <h3 class="text-label-caps text-on-surface-variant mb-lg">{{ $title }}</h3>

    @if ($total <= 0)
        <p class="text-body-sm text-on-surface-variant text-center py-xl">{{ __('hfnms.no_chart_data') }}</p>
    @else
        <div class="flex flex-col sm:flex-row items-center gap-lg">
            <div class="relative w-[140px] h-[140px] shrink-0">
                <div class="w-full h-full rounded-full" style="background: {{ $gradient }}"></div>
                <div class="absolute inset-[18%] bg-surface-container-lowest rounded-full flex flex-col items-center justify-center border border-outline-variant/50">
                    <span class="text-headline-sm font-bold">{{ number_format($total) }}</span>
                    <span class="text-[10px] text-on-surface-variant uppercase">{{ __('hfnms.total') }}</span>
                </div>
            </div>
            <ul class="flex-1 w-full space-y-sm">
                @foreach ($segments as $segment)
                    @if (($segment['value'] ?? 0) > 0)
                        <li class="flex items-center justify-between gap-md text-body-sm">
                            <div class="flex items-center gap-sm min-w-0">
                                <span class="w-3 h-3 rounded-full shrink-0" style="background: {{ $segment['color'] }}"></span>
                                <span class="truncate text-on-surface-variant">{{ $segment['label'] }}</span>
                            </div>
                            <span class="font-mono font-semibold shrink-0">
                                {{ number_format($segment['value']) }}
                                <span class="text-on-surface-variant text-[11px]">({{ $total > 0 ? round(($segment['value'] / $total) * 100) : 0 }}%)</span>
                            </span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif
</div>
