@php $node = $asset->networkNode; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ $node->code }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ $node->name }} · {{ $type->label() }}</p>
            </div>
            <div class="flex items-center flex-wrap gap-sm">
                @if ($type === \App\Enums\NetworkNodeType::Customer)
                    @if ($asset->connection)
                        <a href="{{ route('customer-connections.show', $asset->connection) }}" class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                            {{ __('hfnms.view_connection') }}
                        </a>
                    @elseif(auth()->user()->can('customer.manage'))
                        <a href="{{ route('customer-connections.create', ['customer_id' => $asset->id]) }}" class="px-md py-sm bg-primary-container text-on-primary rounded text-body-sm font-semibold hover:opacity-90">
                            {{ __('hfnms.link_connection') }}
                        </a>
                    @endif
                    <a href="{{ route('path-tracing.index', ['code' => $asset->code]) }}" class="px-md py-sm bg-primary text-on-primary rounded text-body-sm font-semibold hover:opacity-90">
                        {{ __('hfnms.trace_path') }}
                    </a>
                @endif
                @can('network.edit')
                    <a href="{{ route('network.assets.edit', [$type->routeSlug(), $asset->id]) }}" class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                        {{ __('hfnms.edit') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-[1000px] mx-auto space-y-lg">
        <x-network.asset-tabs :active="$type" />

        @if (session('success'))
            <div class="px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.node_info') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.code') }}</dt><dd class="font-mono font-semibold">{{ $node->code }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.status') }}</dt><dd class="capitalize">{{ $node->status->value }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.parent') }}</dt><dd class="font-mono">{{ $node->parent?->code ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">GPS</dt><dd class="font-mono">{{ $node->latitude }}, {{ $node->longitude }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">UUID</dt><dd class="font-mono text-[11px]">{{ $node->uuid }}</dd></div>
                    @if ($node->address)
                        <div><dt class="text-on-surface-variant">{{ __('hfnms.address') }}</dt><dd class="mt-xs">{{ $node->address }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.domain_attributes') }}</h3>
                @include('network.assets.partials.show-' . $type->value, ['asset' => $asset])
            </div>
        </div>

        @if ($type === \App\Enums\NetworkNodeType::Odp && ! empty($odpUpstreamRoute))
            @include('network.assets.partials.odp-upstream-map', [
                'asset' => $asset,
                'route' => $odpUpstreamRoute,
            ])
        @endif

        <div class="space-y-lg">
        @include('network.assets.partials.qr-photos', [
            'type' => $type,
            'asset' => $asset,
            'photos' => $photos,
            'photoCompliance' => $photoCompliance,
            'qrUrl' => $qrUrl,
            'scanUrl' => $scanUrl,
        ])
        </div>

        @can('network.edit')
            <form method="POST" action="{{ route('network.assets.destroy', [$type->routeSlug(), $asset->id]) }}"
                  onsubmit="return confirm(@js(__('hfnms.confirm_delete')))">
                @csrf @method('DELETE')
                <button type="submit" class="text-error text-body-sm font-semibold hover:underline">{{ __('hfnms.delete_asset') }}</button>
            </form>
        @endcan
    </div>
</x-app-layout>
