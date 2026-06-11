@props([
    'title',
    'icon' => 'list',
    'viewAllRoute' => null,
    'viewAllLabel' => null,
    'emptyMessage' => '',
])

<div {{ $attributes->merge(['class' => 'bg-white border border-outline-variant/60 rounded-[12px] shadow-sm overflow-hidden h-full flex flex-col']) }}>
    <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-outline-variant/50 shrink-0">
        <div class="flex items-center gap-2 min-w-0">
            <span class="material-symbols-outlined text-primary text-[18px]">{{ $icon }}</span>
            <h3 class="text-[11px] font-semibold uppercase tracking-wide text-on-surface-variant truncate">{{ $title }}</h3>
        </div>
        @if ($viewAllRoute && $viewAllLabel)
            <a href="{{ $viewAllRoute }}" class="text-[11px] text-primary font-semibold hover:underline shrink-0">{{ $viewAllLabel }}</a>
        @endif
    </div>
    <div class="flex-1 min-h-0">
        {{ $slot }}
    </div>
</div>
