@props(['title' => null])

@php
    $appName = $cmsSettings['branding']['app_name'] ?? config('app.name', 'HFNMS');
    $companyName = $cmsSettings['branding']['company_name'] ?? 'Hinet Fiber';
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
<body class="bg-surface text-on-surface font-sans text-body-md min-h-dvh flex flex-col items-center justify-start sm:justify-center relative overflow-x-hidden overflow-y-auto antialiased py-lg sm:py-xl">
    <div class="absolute inset-0 technical-pattern z-0 pointer-events-none"></div>
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary opacity-5 blur-[120px] rounded-full pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-secondary opacity-5 blur-[120px] rounded-full pointer-events-none"></div>

    <main class="relative z-10 w-full max-w-[420px] px-lg flex-shrink-0">
        <div class="flex flex-col items-center mb-lg sm:mb-xl">
            <div class="mb-md flex items-center justify-center bg-primary-container p-sm rounded-lg shadow-sm">
                <span class="material-symbols-outlined text-on-primary-container text-[32px]" style="font-variation-settings: 'FILL' 1;">router</span>
            </div>
            <h1 class="font-semibold text-headline-md text-primary tracking-tight">{{ $companyName }}</h1>
            <p class="text-label-caps text-on-surface-variant uppercase mt-xs">{{ __('hfnms.enterprise_management_system') }}</p>
        </div>

        {{ $slot }}

        <div class="mt-xl grid grid-cols-1 overflow-hidden rounded-xl border border-outline-variant bg-surface-container shadow-sm h-[140px] relative">
            <img
                class="w-full h-full object-cover grayscale opacity-40 mix-blend-multiply"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuDoflk25C3rKkLskINlmAd1dB4DCjU8t4Z8f2i6ikfnJcrpnL1DL9mY0685Z7YLHRL1DpyoUuN8xGhUhl29DOVakWO5kSkT2ndIt1eSErqGWRUl7BXTrnX9YrH2XJJPHct9mUhh4t5GOq9Kmd4wramtQ0CusY5lVR1ZXL9i1q29p3gU4F738zUTGB8UCZM3Ysgy1dWGkH1Jvw6Q2dKvtRf9V5suNillSx0GUKOEUMzCDJHPoDIcTrlKU0XNgVT8TmtApnIr0GA9Qds8"
                alt="Server room fiber infrastructure"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-surface-container to-transparent flex flex-col justify-end p-md">
                <div class="flex items-center gap-xs">
                    <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span>
                    <span class="text-label-caps text-on-surface-variant uppercase">{{ __('hfnms.backbone_status') }}</span>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-lg sm:mt-xl relative z-10 w-full flex justify-center pb-lg sm:pb-xl flex-shrink-0">
        <x-language-switcher />
    </footer>
</body>
</html>
