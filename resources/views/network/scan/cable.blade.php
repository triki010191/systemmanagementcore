<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $cable->code }} — HFNMS</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-surface text-on-surface font-sans antialiased min-h-screen">
    <div class="max-w-lg mx-auto p-lg">
        <div class="text-center mb-lg">
            <h1 class="font-semibold text-headline-md text-primary font-mono">{{ $cable->code }}</h1>
            <p class="text-body-sm text-on-surface-variant">{{ $cable->name }}</p>
            <span class="inline-block mt-sm px-sm py-xs bg-surface-container rounded text-label-caps">{{ $cable->core_count }} CORE</span>
        </div>

        @if ($qrUrl)
            <div class="flex justify-center mb-lg">
                <img src="{{ $qrUrl }}" alt="QR" class="w-48 h-48 border border-outline-variant rounded-lg">
            </div>
        @endif

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-sm text-body-sm">
            <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('hfnms.endpoints') }}</span><span class="font-mono">{{ $cable->startNode?->code }} → {{ $cable->endNode?->code }}</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('hfnms.length') }}</span><span>{{ number_format($cable->length_meters ?? 0) }} m</span></div>
            <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('hfnms.status') }}</span><span class="capitalize">{{ $cable->status }}</span></div>
        </div>
    </div>
</body>
</html>
