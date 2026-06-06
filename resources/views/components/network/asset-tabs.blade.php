@props(['active'])

<nav class="flex flex-wrap gap-xs mb-lg border-b border-outline-variant pb-sm">
    @foreach (\App\Enums\NetworkNodeType::cases() as $tabType)
        <a href="{{ route('network.assets.index', $tabType->routeSlug()) }}"
           class="px-md py-sm text-body-sm font-semibold rounded-t transition-colors {{ $active === $tabType ? 'bg-primary text-on-primary' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
            {{ $tabType->label() }}
        </a>
    @endforeach
</nav>
