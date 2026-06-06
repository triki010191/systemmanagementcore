<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-md flex-wrap">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ $cable->code }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ $cable->name }} · {{ $cable->core_count }} core</p>
            </div>
            <a href="{{ route('cables.index') }}" class="ml-auto text-body-sm text-primary font-semibold hover:underline">← {{ __('hfnms.back') }}</a>
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto space-y-lg">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-lg">
                <p class="text-label-caps text-outline">{{ __('hfnms.manufacturer') }}</p>
                <p class="font-semibold mt-xs">{{ $cable->manufacturer ?? '—' }}</p>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-lg">
                <p class="text-label-caps text-outline">{{ __('hfnms.fiber_type') }}</p>
                <p class="font-semibold mt-xs">{{ $cable->fiber_type ?? '—' }}</p>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-lg">
                <p class="text-label-caps text-outline">{{ __('hfnms.length') }}</p>
                <p class="font-semibold mt-xs">{{ number_format($cable->length_meters ?? 0) }} m</p>
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant p-md rounded-lg">
                <p class="text-label-caps text-outline">{{ __('hfnms.endpoints') }}</p>
                <p class="font-mono text-body-sm mt-xs">{{ $cable->startNode?->code }} → {{ $cable->endNode?->code }}</p>
            </div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
            <h3 class="text-label-caps text-on-surface-variant mb-lg">{{ __('hfnms.tube_core_matrix') }}</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-md">
                @foreach ($tubes as $tube)
                    @php $cores = $coresByTube->get($tube->tube_number, collect()); @endphp
                    <div class="border border-outline-variant rounded-lg overflow-hidden">
                        <div class="px-md py-sm flex items-center gap-sm" style="background-color: {{ $tube->tubeColor?->hex_code ?? '#eceef0' }}20">
                            <span class="w-4 h-4 rounded-full border border-outline-variant" style="background-color: {{ $tube->tubeColor?->hex_code ?? '#ccc' }}"></span>
                            <span class="font-semibold text-body-sm">Tube {{ $tube->tube_number }} — {{ $tube->tubeColor?->color_name }}</span>
                        </div>
                        <div class="p-sm grid grid-cols-4 gap-1">
                            @foreach ($cores->sortBy('core_position_in_tube') as $core)
                                @php
                                    $statusColor = match ($core->status->value) {
                                        'used' => 'bg-primary text-white',
                                        'broken' => 'bg-error text-white',
                                        'reserved' => 'bg-yellow-500 text-white',
                                        default => 'bg-surface-container text-on-surface-variant',
                                    };
                                @endphp
                                <div class="text-center py-1 rounded text-[10px] font-mono {{ $statusColor }}" title="Core {{ $core->core_number }}">
                                    {{ $core->core_position_in_tube ?? $core->core_number }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
            <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.qr_code') }}</h3>
            <div class="flex flex-col sm:flex-row gap-lg items-start">
                @if ($qrUrl)
                    <img src="{{ $qrUrl }}" alt="QR" class="w-40 h-40 border border-outline-variant rounded-lg">
                @else
                    <div class="w-40 h-40 border border-dashed border-outline-variant rounded-lg flex items-center justify-center text-on-surface-variant text-body-sm text-center p-sm">{{ __('hfnms.qr_not_generated') }}</div>
                @endif
                <div class="flex-1 space-y-sm text-body-sm">
                    <p class="font-mono text-[12px] break-all bg-surface-container-low p-sm rounded">{{ $scanUrl }}</p>
                    @can('cable.manage')
                        <form method="POST" action="{{ route('cables.qr.generate', $cable) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded text-body-sm font-semibold hover:opacity-90">
                                <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                                {{ $qrUrl ? __('hfnms.regenerate_qr') : __('hfnms.generate_qr') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
