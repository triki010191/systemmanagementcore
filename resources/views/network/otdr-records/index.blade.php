<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.otdr_mapping') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.otdr_mapping_subtitle') }}</p>
            </div>
            @can('cable.manage')
                <a href="{{ route('otdr-records.create') }}"
                   class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    {{ __('hfnms.add_otdr_record') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        @if (session('success'))
            <div class="mb-md px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.measured_at') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.cable_core') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.total_loss') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.fault_distance') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.technician') }}</th>
                        <th class="text-left px-lg py-sm">.sor</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm">{{ $record->measured_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="px-lg py-sm font-mono text-[12px]">
                                {{ $record->cableCore?->cable?->code }} #{{ $record->cableCore?->core_number }}
                            </td>
                            <td class="px-lg py-sm font-mono {{ ($record->total_loss_db ?? 0) >= 0.35 ? 'text-error font-bold' : '' }}">
                                {{ $record->total_loss_db !== null ? number_format($record->total_loss_db, 2).' dB' : '—' }}
                            </td>
                            <td class="px-lg py-sm font-mono">{{ $record->fault_distance_km !== null ? number_format($record->fault_distance_km, 3).' km' : '—' }}</td>
                            <td class="px-lg py-sm">{{ $record->technician?->name ?? '—' }}</td>
                            <td class="px-lg py-sm">
                                @if ($record->trace_file_path)
                                    <span class="material-symbols-outlined text-success text-[18px]" title=".sor">description</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-lg py-sm text-right space-x-sm">
                                <a href="{{ route('otdr-records.show', $record) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view') }}</a>
                                @can('cable.manage')
                                    <a href="{{ route('otdr-records.edit', $record) }}" class="text-on-surface-variant hover:text-primary">{{ __('hfnms.edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_otdr_records') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <div class="mt-md">{{ $records->links() }}</div>
        @endif
    </div>
</x-app-layout>
