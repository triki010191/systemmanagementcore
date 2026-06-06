<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $node->code }} — HFNMS Scan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-surface text-on-surface font-sans antialiased min-h-screen">
    <div class="max-w-lg mx-auto p-lg">
        <div class="text-center mb-lg">
            <div class="inline-flex items-center justify-center bg-primary-container p-sm rounded-lg mb-md">
                <span class="material-symbols-outlined text-on-primary-container text-[32px]">qr_code_scanner</span>
            </div>
            <h1 class="font-semibold text-headline-md text-primary">{{ $node->code }}</h1>
            <p class="text-body-sm text-on-surface-variant">{{ $node->name }}</p>
            <span class="inline-block mt-sm px-sm py-xs bg-surface-container rounded text-label-caps uppercase">{{ $node->type->label() }}</span>
        </div>

        @if ($qrUrl)
            <div class="flex justify-center mb-lg">
                <img src="{{ $qrUrl }}" alt="QR {{ $node->code }}" class="w-48 h-48 border border-outline-variant rounded-lg">
            </div>
        @endif

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-sm text-body-sm mb-lg">
            <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('hfnms.status') }}</span><span class="capitalize">{{ $node->status->value }}</span></div>
            @if ($node->parent)
                <div class="flex justify-between"><span class="text-on-surface-variant">{{ __('hfnms.parent') }}</span><span class="font-mono">{{ $node->parent->code }}</span></div>
            @endif
            <div class="flex justify-between"><span class="text-on-surface-variant">GPS</span><span class="font-mono text-[12px]">{{ $node->latitude }}, {{ $node->longitude }}</span></div>
            @if ($node->address)
                <div><span class="text-on-surface-variant">{{ __('hfnms.address') }}</span><p class="mt-xs">{{ $node->address }}</p></div>
            @endif
        </div>

        @if ($photos->isNotEmpty())
            <div class="grid grid-cols-2 gap-sm mb-lg">
                @foreach ($photos->take(4) as $photo)
                    <img src="{{ Storage::disk('public')->url($photo->file_path) }}" alt="{{ $photo->caption }}" class="rounded-lg border border-outline-variant aspect-video object-cover">
                @endforeach
            </div>
        @endif

        @auth
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="block text-center text-primary font-semibold text-body-sm">{{ __('hfnms.back') }}</a>
        @else
            <a href="{{ route('login') }}" class="block text-center px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm">{{ __('hfnms.login_for_details') }}</a>
        @endauth
    </div>
</body>
</html>
