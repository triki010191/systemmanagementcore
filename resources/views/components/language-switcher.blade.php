@props(['variant' => 'light'])

@php
    $currentLocale = app()->getLocale();
    $wrapperClass = $variant === 'dark'
        ? 'flex items-center gap-sm text-tertiary-fixed-dim'
        : 'flex items-center gap-md bg-surface-container-low px-md py-xs rounded-full border border-outline-variant shadow-sm';
    $activeClass = $variant === 'dark' ? 'text-on-tertiary font-bold' : 'text-primary font-bold';
    $inactiveClass = $variant === 'dark' ? 'text-tertiary-fixed-dim hover:text-on-tertiary' : 'text-on-surface-variant hover:text-primary';
@endphp

<div class="{{ $wrapperClass }}">
    <a
        href="{{ route('locale.switch', 'id') }}"
        class="flex items-center gap-xs text-body-sm transition-all {{ $currentLocale === 'id' ? $activeClass : $inactiveClass }}"
    >
        <span class="material-symbols-outlined text-[16px]">language</span>
        <span>{{ __('hfnms.language_id') }}</span>
    </a>
    <div class="w-px h-3 bg-outline-variant"></div>
    <a
        href="{{ route('locale.switch', 'en') }}"
        class="text-body-sm transition-all {{ $currentLocale === 'en' ? $activeClass : $inactiveClass }}"
    >
        {{ __('hfnms.language_en') }}
    </a>
</div>
