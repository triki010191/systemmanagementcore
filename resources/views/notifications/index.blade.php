<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.notifications') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.notifications_subtitle') }}</p>
            </div>
            @if (auth()->user()->unreadNotifications()->count() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                        {{ __('hfnms.mark_all_read') }}
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="max-w-[900px] mx-auto">
        @if (session('success'))
            <div class="mb-md px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg divide-y divide-outline-variant">
            @forelse ($notifications as $notification)
                @php $data = $notification->data; @endphp
                <a href="{{ route('notifications.read', $notification->id) }}"
                   class="block px-lg py-md hover:bg-surface-container-low/50 {{ $notification->read_at ? 'opacity-70' : 'bg-primary-fixed/10' }}">
                    <div class="flex justify-between gap-md">
                        <div>
                            <p class="text-body-sm font-semibold">{{ $data['message'] ?? $data['title'] ?? '—' }}</p>
                            <p class="text-[11px] text-on-surface-variant mt-xs">{{ $notification->created_at->format('d M Y H:i') }}</p>
                        </div>
                        @unless ($notification->read_at)
                            <span class="w-2 h-2 rounded-full bg-primary shrink-0 mt-1"></span>
                        @endunless
                    </div>
                </a>
            @empty
                <p class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_notifications') }}</p>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="mt-md">{{ $notifications->links() }}</div>
        @endif
    </div>
</x-app-layout>
