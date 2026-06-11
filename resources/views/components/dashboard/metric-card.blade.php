@props([
    'label',
    'value',
    'icon' => 'analytics',
    'subtitle' => null,
    'progress' => null,
    'accent' => 'blue',
    'status' => null,
])

@php
    $accents = [
        'blue' => ['grad' => 'from-blue-50 via-white to-white', 'border' => 'border-blue-100', 'icon' => 'bg-blue-100 text-blue-600', 'value' => 'text-blue-700', 'bar' => 'bg-blue-500'],
        'emerald' => ['grad' => 'from-emerald-50 via-white to-white', 'border' => 'border-emerald-100', 'icon' => 'bg-emerald-100 text-emerald-600', 'value' => 'text-emerald-700', 'bar' => 'bg-emerald-500'],
        'amber' => ['grad' => 'from-amber-50 via-white to-white', 'border' => 'border-amber-100', 'icon' => 'bg-amber-100 text-amber-600', 'value' => 'text-amber-700', 'bar' => 'bg-amber-500'],
        'rose' => ['grad' => 'from-rose-50 via-white to-white', 'border' => 'border-rose-100', 'icon' => 'bg-rose-100 text-rose-600', 'value' => 'text-rose-700', 'bar' => 'bg-rose-500'],
        'violet' => ['grad' => 'from-violet-50 via-white to-white', 'border' => 'border-violet-100', 'icon' => 'bg-violet-100 text-violet-600', 'value' => 'text-violet-700', 'bar' => 'bg-violet-500'],
        'orange' => ['grad' => 'from-orange-50 via-white to-white', 'border' => 'border-orange-100', 'icon' => 'bg-orange-100 text-orange-600', 'value' => 'text-orange-700', 'bar' => 'bg-orange-500'],
    ];
    $a = $accents[$accent] ?? $accents['blue'];

    $statusDot = match ($status) {
        'healthy' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'critical' => 'bg-red-500',
        default => null,
    };
    $valueOverride = match ($status) {
        'critical' => 'text-red-600',
        'warning' => 'text-amber-600',
        default => null,
    };
    $valueColor = $valueOverride ?? $a['value'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-gradient-to-br '.$a['grad'].' border '.$a['border'].' rounded-[12px] p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between min-h-[104px]']) }}>
    <div class="flex items-start justify-between gap-2">
        <div class="flex items-center gap-1.5 min-w-0">
            @if ($statusDot)
                <span class="w-2 h-2 rounded-full {{ $statusDot }} shrink-0 animate-pulse"></span>
            @endif
            <span class="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant truncate">{{ $label }}</span>
        </div>
        <div class="p-1.5 rounded-lg {{ $a['icon'] }} shrink-0">
            <span class="material-symbols-outlined text-[18px]">{{ $icon }}</span>
        </div>
    </div>
    <div class="mt-2">
        <span class="text-[28px] font-bold leading-none tracking-tight {{ $valueColor }}">{{ $value }}</span>
        @if ($subtitle)
            <p class="text-[11px] text-on-surface-variant mt-1 leading-snug">{{ $subtitle }}</p>
        @endif
        @if ($progress !== null)
            <div class="w-full bg-white/70 h-1.5 rounded-full overflow-hidden mt-2 border border-black/5">
                <div class="{{ $a['bar'] }} h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $progress)) }}%"></div>
            </div>
        @endif
    </div>
</div>
