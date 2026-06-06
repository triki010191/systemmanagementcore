<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.otdr_record_detail') }}</h2>
                <p class="text-body-sm text-on-surface-variant font-mono">
                    {{ $record->cableCore?->cable?->code }} — Core #{{ $record->cableCore?->core_number }}
                </p>
            </div>
            @can('cable.manage')
                <a href="{{ route('otdr-records.edit', $record) }}"
                   class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                    {{ __('hfnms.edit') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1000px] mx-auto space-y-lg">
        @if (session('success'))
            <div class="px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.measurement_data') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.total_loss') }}</dt>
                        <dd class="font-mono font-semibold {{ ($record->total_loss_db ?? 0) >= 0.35 ? 'text-error' : '' }}">
                            {{ $record->total_loss_db !== null ? number_format($record->total_loss_db, 2).' dB' : '—' }}
                        </dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.distance') }}</dt>
                        <dd class="font-mono">{{ $record->distance_km !== null ? number_format($record->distance_km, 3).' km' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.fault_distance') }}</dt>
                        <dd class="font-mono">{{ $record->fault_distance_km !== null ? number_format($record->fault_distance_km, 3).' km' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.measured_at') }}</dt>
                        <dd>{{ $record->measured_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.technician') }}</dt>
                        <dd>{{ $record->technician?->name ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.cable_core') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.cable_code') }}</dt>
                        <dd class="font-mono">{{ $record->cableCore?->cable?->code ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.core_number') }}</dt>
                        <dd class="font-mono">#{{ $record->cableCore?->core_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">Tube</dt>
                        <dd class="font-mono">{{ $record->cableCore?->tube_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.status') }}</dt>
                        <dd>{{ $record->cableCore?->status?->label() ?? '—' }}</dd></div>
                </dl>
                @if ($record->cableCore?->cable)
                    <a href="{{ route('cables.show', $record->cableCore->cable) }}" class="inline-block mt-md text-primary text-body-sm font-semibold hover:underline">
                        {{ __('hfnms.view_cable') }}
                    </a>
                @endif
            </div>
        </div>

        @if ($record->event_points && count($record->event_points) > 0)
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.otdr_event_points') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm">
                        <thead>
                            <tr class="border-b border-outline-variant text-left text-on-surface-variant">
                                <th class="py-sm pr-md">#</th>
                                <th class="py-sm pr-md">{{ __('hfnms.distance') }}</th>
                                <th class="py-sm">{{ __('hfnms.loss_db') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($record->event_points as $event)
                                <tr class="border-b border-outline-variant/50">
                                    <td class="py-sm pr-md font-mono">{{ $event['index'] ?? $loop->iteration }}</td>
                                    <td class="py-sm pr-md font-mono">{{ isset($event['distance_km']) ? number_format($event['distance_km'], 3).' km' : '—' }}</td>
                                    <td class="py-sm font-mono">{{ isset($event['loss_db']) ? number_format($event['loss_db'], 2).' dB' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($record->notes)
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.notes') }}</h3>
                <p class="text-body-sm whitespace-pre-wrap">{{ $record->notes }}</p>
            </div>
        @endif

        @if ($record->trace_file_path)
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.trace_file') }}</h3>
                <a href="{{ route('otdr-records.trace.download', $record) }}"
                   class="inline-flex items-center gap-sm px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    {{ __('hfnms.download_trace') }} — {{ basename($record->trace_file_path) }}
                </a>
            </div>
        @endif

        <div class="flex items-center gap-md">
            <a href="{{ route('otdr-records.index') }}" class="text-primary text-body-sm font-semibold hover:underline">← {{ __('hfnms.back_to_list') }}</a>
            @can('cable.manage')
                <form method="POST" action="{{ route('otdr-records.destroy', $record) }}"
                      onsubmit="return confirm('{{ __('hfnms.confirm_delete_otdr') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-error text-body-sm font-semibold hover:underline">{{ __('hfnms.delete_otdr_record') }}</button>
                </form>
            @endcan
        </div>
    </div>
</x-app-layout>
