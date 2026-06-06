@php
    $appName = $cmsSettings['branding']['app_name'] ?? config('app.name', 'HFNMS');
    $companyName = $cmsSettings['branding']['company_name'] ?? 'Hinet Fiber';
    $iconMap = [
        'home' => 'dashboard',
        'arrow-path' => 'route',
        'map' => 'map',
        'cable' => 'settings_input_component',
        'circle-stack' => 'memory',
        'share' => 'lan',
        'users' => 'group',
        'exclamation-triangle' => 'confirmation_number',
        'cog' => 'settings',
        'chart-bar' => 'analytics',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface text-on-surface font-sans text-body-md overflow-hidden flex h-screen antialiased">
    <aside class="fixed left-0 top-0 h-full w-[260px] overflow-y-auto z-50 bg-on-tertiary-fixed border-r border-outline-variant flex flex-col custom-scrollbar">
        <div class="p-lg">
            <h1 class="font-semibold text-headline-md text-surface-container-lowest tracking-tight">{{ $companyName }}</h1>
            <p class="text-label-caps text-tertiary-fixed-dim opacity-70 mt-xs">{{ __('hfnms.enterprise_management_system') }}</p>
        </div>

        <nav class="flex-1 px-sm">
            <ul class="space-y-px">
                @foreach ($cmsNavigation ?? [] as $item)
                    @php
                        $isActive = ($item->route_name && request()->routeIs($item->route_name.'*'))
                            || ($item->url && $item->url !== '#' && request()->is(ltrim($item->url, '/').'*'));
                        $icon = $iconMap[$item->icon] ?? 'circle';
                        $href = $item->route_name ? route($item->route_name) : ($item->url ?? '#');
                    @endphp
                    <li>
                        <a href="{{ $href }}"
                           class="flex items-center px-lg py-md transition-colors duration-200 border-l-[3px] {{ $isActive
                               ? 'border-primary-container bg-on-tertiary-fixed-variant text-on-tertiary-container font-semibold'
                               : 'border-transparent text-tertiary-fixed-dim hover:text-on-tertiary hover:bg-on-tertiary-fixed-variant' }}">
                            <span class="material-symbols-outlined mr-md text-[20px]">{{ $icon }}</span>
                            <span class="text-body-md">{{ $item->label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="p-lg border-t border-on-tertiary-fixed-variant">
            <x-language-switcher variant="dark" />
        </div>
    </aside>

    <main class="ml-[260px] flex-1 flex flex-col h-screen overflow-hidden bg-surface">
        <header class="flex justify-between items-center h-16 px-lg bg-surface border-b border-outline-variant z-40 sticky top-0 shrink-0">
            <div class="flex items-center gap-md min-w-0">
                @isset($header)
                    <div class="min-w-0">{{ $header }}</div>
                @else
                    <span class="font-semibold text-headline-md text-primary truncate">{{ $companyName }}</span>
                @endisset
            </div>
            <div class="flex items-center gap-md shrink-0" x-data="notificationBell()" x-init="load()">
                <div class="relative">
                    <button type="button" @click="open = !open" class="relative p-xs rounded hover:bg-surface-container-low">
                        <span class="material-symbols-outlined text-on-surface-variant hover:text-primary transition-colors">notifications</span>
                        <span x-show="count > 0" x-text="count > 9 ? '9+' : count"
                              class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-error text-white text-[10px] font-bold flex items-center justify-center"></span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-sm w-80 bg-surface-container-lowest border border-outline-variant rounded-lg shadow-lg z-50 overflow-hidden">
                        <div class="px-md py-sm border-b border-outline-variant flex justify-between items-center">
                            <span class="text-label-caps text-on-surface-variant">{{ __('hfnms.notifications') }}</span>
                            <a href="{{ route('notifications.index') }}" class="text-[11px] text-primary font-semibold">{{ __('hfnms.view_all') }}</a>
                        </div>
                        <template x-if="items.length === 0">
                            <p class="px-md py-lg text-body-sm text-on-surface-variant text-center">{{ __('hfnms.no_notifications') }}</p>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <a :href="item.url" class="block px-md py-sm border-b border-outline-variant/50 hover:bg-surface-container-low text-body-sm">
                                <p x-text="item.message" class="line-clamp-2"></p>
                                <p x-text="item.created_at" class="text-[10px] text-on-surface-variant mt-xs"></p>
                            </a>
                        </template>
                    </div>
                </div>
                <div class="h-8 w-px bg-outline-variant"></div>
                <div class="flex items-center gap-sm">
                    <div class="text-right hidden sm:block">
                        <p class="text-label-caps text-on-surface leading-tight">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-outline">{{ Auth::user()->getRoleNames()->first() ?? 'User' }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="h-9 w-9 rounded-full bg-primary-container flex items-center justify-center text-on-primary text-sm font-bold">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </a>
                </div>
            </div>
        </header>

        <section class="flex-1 overflow-y-auto p-lg custom-scrollbar">
            {{ $slot }}
        </section>
    </main>
    @auth
    <script>
        function notificationBell() {
            return {
                open: false,
                count: 0,
                items: [],
                async load() {
                    try {
                        const res = await fetch('{{ route('notifications.unread') }}');
                        const data = await res.json();
                        this.count = data.count;
                        this.items = data.items;
                    } catch (e) {}
                }
            }
        }
    </script>
    @endauth
</body>
</html>
