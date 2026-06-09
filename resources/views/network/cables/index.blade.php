<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.cable_management') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.cable_management_subtitle') }}</p>
            </div>
            @can('cable.manage')
                <a href="{{ route('cables.create') }}"
                   class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    {{ __('hfnms.add_cable') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.cable_code') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.cable_name') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.core_count') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.endpoints') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.length') }}</th>
                        <th class="text-left px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cables as $cable)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm font-mono text-primary font-semibold">{{ $cable->code }}</td>
                            <td class="px-lg py-sm">{{ $cable->name }}</td>
                            <td class="px-lg py-sm">{{ $cable->core_count }} core ({{ $cable->cores_count }} {{ __('hfnms.provisioned') }})</td>
                            <td class="px-lg py-sm text-on-surface-variant">
                                {{ $cable->startNode?->code ?? '—' }} → {{ $cable->endNode?->code ?? '—' }}
                            </td>
                            <td class="px-lg py-sm">{{ number_format($cable->length_meters ?? 0) }} m</td>
                            <td class="px-lg py-sm text-right space-x-sm">
                                <a href="{{ route('cables.show', $cable) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view_detail') }}</a>
                                @can('cable.manage')
                                    <a href="{{ route('cables.edit', $cable) }}" class="text-on-surface-variant font-semibold hover:underline">{{ __('hfnms.edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-lg py-lg text-center text-on-surface-variant">{{ __('hfnms.no_cables') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-md">{{ $cables->links() }}</div>
    </div>
</x-app-layout>
