@props([
    'label',
    'value',
    'icon' => 'analytics',
    'subtitle' => null,
    'progress' => null,
    'progressColor' => null,
    'valueClass' => '',
    'accent' => 'blue',
])

@php
    $themes = [
        'blue'    => ['card' => 'from-blue-50 to-white border-blue-200/80',    'icon' => 'bg-blue-500/15 text-blue-600',    'bar' => 'bg-blue-500'],
        'indigo'  => ['card' => 'from-indigo-50 to-white border-indigo-200/80',  'icon' => 'bg-indigo-500/15 text-indigo-600',  'bar' => 'bg-indigo-500'],
        'violet'  => ['card' => 'from-violet-50 to-white border-violet-200/80',  'icon' => 'bg-violet-500/15 text-violet-600',  'bar' => 'bg-violet-500'],
        'cyan'    => ['card' => 'from-cyan-50 to-white border-cyan-200/80',    'icon' => 'bg-cyan-500/15 text-cyan-600',    'bar' => 'bg-cyan-500'],
        'amber'   => ['card' => 'from-amber-50 to-white border-amber-200/80',   'icon' => 'bg-amber-500/15 text-amber-600',   'bar' => 'bg-amber-500'],
        'rose'    => ['card' => 'from-rose-50 to-white border-rose-200/80',    'icon' => 'bg-rose-500/15 text-rose-600',    'bar' => 'bg-rose-500'],
        'emerald' => ['card' => 'from-emerald-50 to-white border-emerald-200/80', 'icon' => 'bg-emerald-500/15 text-emerald-600', 'bar' => 'bg-emerald-500'],
        'orange'  => ['card' => 'from-orange-50 to-white border-orange-200/80',  'icon' => 'bg-orange-500/15 text-orange-600',  'bar' => 'bg-orange-500'],
        'sky'     => ['card' => 'from-sky-50 to-white border-sky-200/80',     'icon' => 'bg-sky-500/15 text-sky-600',     'bar' => 'bg-sky-500'],
        'teal'    => ['card' => 'from-teal-50 to-white border-teal-200/80',    'icon' => 'bg-teal-500/15 text-teal-600',    'bar' => 'bg-teal-500'],
        'fuchsia' => ['card' => 'from-fuchsia-50 to-white border-fuchsia-200/80', 'icon' => 'bg-fuchsia-500/15 text-fuchsia-600', 'bar' => 'bg-fuchsia-500'],
    ];
    $theme = $themes[$accent] ?? $themes['blue'];
    $barColor = $progressColor ?? $theme['bar'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-gradient-to-br '.$theme['card'].' border p-md rounded-xl flex flex-col justify-between min-h-[128px] shadow-sm hover:shadow-md transition-shadow']) }}>
    <div class="flex justify-between items-start gap-sm">
        <span class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant/80 leading-tight">{{ $label }}</span>
        <div class="p-sm rounded-lg {{ $theme['icon'] }} shrink-0">
            <span class="material-symbols-outlined text-[22px]">{{ $icon }}</span>
        </div>
    </div>
    <div class="mt-md">
        <span class="text-[30px] font-bold leading-none tracking-tight {{ $valueClass }}">{{ $value }}</span>
        @if ($subtitle)
            <p class="text-[11px] text-on-surface-variant mt-xs leading-snug">{{ $subtitle }}</p>
        @endif
        @if ($progress !== null)
            <div class="w-full bg-white/60 h-2 rounded-full overflow-hidden mt-sm border border-black/5">
                <div class="{{ $barColor }} h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(0, $progress)) }}%"></div>
            </div>
        @endif
    </div>
</div>
