<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.network_assets') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ $type->label() }}</p>
            </div>
            @can('network.create')
                <a href="{{ route('network.assets.create', $type->routeSlug()) }}"
                   class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    {{ __('hfnms.add_asset', ['type' => $type->label()]) }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        <x-network.asset-tabs :active="$type" />

        @if (session('success'))
            <div class="mb-md px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('form'))
            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $errors->first('form') }}</div>
        @endif

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.code') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.name') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.parent') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                        <th class="text-left px-lg py-sm">GPS</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assets as $record)
                        @php $node = $record->networkNode; @endphp
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm font-mono text-primary font-semibold">{{ $node->code }}</td>
                            <td class="px-lg py-sm">{{ $node->name }}</td>
                            <td class="px-lg py-sm text-on-surface-variant">{{ $node->parent?->code ?? '—' }}</td>
                            <td class="px-lg py-sm">
                                <span class="px-sm py-xs rounded text-[11px] font-bold uppercase bg-surface-container">{{ $node->status->value }}</span>
                            </td>
                            <td class="px-lg py-sm font-mono text-[12px]">{{ $node->latitude }}, {{ $node->longitude }}</td>
                            <td class="px-lg py-sm text-right space-x-sm whitespace-nowrap">
                                <a href="{{ route('network.assets.show', [$type->routeSlug(), $record->id]) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view') }}</a>
                                @can('network.edit')
                                    <a href="{{ route('network.assets.edit', [$type->routeSlug(), $record->id]) }}" class="text-on-surface-variant hover:text-primary">{{ __('hfnms.edit') }}</a>
                                    <form method="POST" action="{{ route('network.assets.destroy', [$type->routeSlug(), $record->id]) }}" class="inline"
                                          onsubmit="return confirm(@js(__('hfnms.confirm_delete')))">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-error hover:underline">{{ __('hfnms.delete') }}</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-lg py-lg text-center text-on-surface-variant">{{ __('hfnms.no_assets') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-md">{{ $assets->links() }}</div>
    </div>
</x-app-layout>
