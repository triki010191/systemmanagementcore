<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.core_management') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.core_management_subtitle') }}</p>
            </div>
            @can('cable.manage')
                <a href="{{ route('cables.index') }}" class="text-primary text-body-sm font-semibold hover:underline">
                    {{ __('hfnms.view_cables') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-sm mb-md">
            @foreach ($statuses as $status)
                @php $count = $statusCounts[$status->value] ?? 0; @endphp
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-sm">
                    <p class="text-[11px] text-on-surface-variant uppercase">{{ $status->label() }}</p>
                    <p class="font-mono font-semibold text-headline-sm">{{ number_format($count) }}</p>
                </div>
            @endforeach
        </div>

        <form method="GET" class="mb-md flex flex-wrap gap-sm items-end bg-surface-container-lowest border border-outline-variant rounded-lg p-md">
            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable') }}</label>
                <select name="cable_id" class="rounded-lg border-outline-variant text-body-sm min-w-[200px]">
                    <option value="">{{ __('hfnms.all') }}</option>
                    @foreach ($cables as $cable)
                        <option value="{{ $cable->id }}" @selected((string) $filters['cable_id'] === (string) $cable->id)>
                            {{ $cable->code }} — {{ $cable->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }}</label>
                <select name="status" class="rounded-lg border-outline-variant text-body-sm">
                    <option value="">{{ __('hfnms.all') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                {{ __('hfnms.filter') }}
            </button>
        </form>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-hidden">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.cable') }}</th>
                        <th class="text-left px-lg py-sm">Tube</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.core_number') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.total_loss') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.route') }}</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cores as $core)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm font-mono text-[12px]">
                                @can('cable.manage')
                                    <a href="{{ route('cables.show', $core->cable) }}" class="text-primary hover:underline">{{ $core->cable?->code ?? '—' }}</a>
                                @else
                                    {{ $core->cable?->code ?? '—' }}
                                @endcan
                            </td>
                            <td class="px-lg py-sm font-mono">#{{ $core->tube_number }}</td>
                            <td class="px-lg py-sm font-mono">#{{ $core->core_number }}</td>
                            <td class="px-lg py-sm">{{ $core->status?->label() ?? '—' }}</td>
                            <td class="px-lg py-sm font-mono">{{ $core->loss_db !== null ? number_format($core->loss_db, 2).' dB' : '—' }}</td>
                            <td class="px-lg py-sm text-[12px]">
                                {{ $core->sourceNode?->code ?? '—' }} → {{ $core->targetNode?->code ?? '—' }}
                            </td>
                            <td class="px-lg py-sm text-right">
                                @can('cable.manage')
                                    <a href="{{ route('cables.show', $core->cable) }}#core-{{ $core->id }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_cores') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cores->hasPages())
            <div class="mt-md">{{ $cores->links() }}</div>
        @endif
    </div>
</x-app-layout>
