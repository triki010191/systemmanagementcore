@php
    $isEdit = $record !== null;
    $action = $isEdit ? route('otdr-records.update', $record) : route('otdr-records.store');
    $selectedCore = old('cable_core_id', $record?->cable_core_id ?? $preselectedCoreId);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">
                {{ $isEdit ? __('hfnms.edit_otdr_record') : __('hfnms.add_otdr_record') }}
            </h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.otdr_form_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[900px] mx-auto" x-data="{
        parseMessage: '',
        parseOk: false,
        async parseSor(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.parseMessage = '{{ __('hfnms.sor_parsing') }}';
            const fd = new FormData();
            fd.append('trace_file', file);
            fd.append('_token', '{{ csrf_token() }}');
            try {
                const res = await fetch('{{ route('otdr-records.parse-sor') }}', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.total_loss_db != null) document.querySelector('[name=total_loss_db]').value = data.total_loss_db;
                if (data.distance_km != null) document.querySelector('[name=distance_km]').value = data.distance_km;
                if (data.fault_distance_km != null) document.querySelector('[name=fault_distance_km]').value = data.fault_distance_km;
                this.parseOk = data.success;
                this.parseMessage = data.message || '';
            } catch (e) {
                this.parseMessage = '{{ __('hfnms.sor_parse_failed') }}';
                this.parseOk = false;
            }
        }
    }">
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_core') }} *</label>
                <select name="cable_core_id" required class="w-full rounded-lg border-outline-variant text-body-sm font-mono">
                    <option value="">{{ __('hfnms.select_core') }}</option>
                    @foreach ($cores as $core)
                        <option value="{{ $core->id }}" @selected($selectedCore == $core->id)>
                            {{ $core->cable?->code }} — Core #{{ $core->core_number }} (Tube {{ $core->tube_number }})
                        </option>
                    @endforeach
                </select>
                @error('cable_core_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.total_loss') }} (dB)</label>
                    <input type="number" step="0.01" name="total_loss_db" value="{{ old('total_loss_db', $record?->total_loss_db) }}"
                           class="w-full rounded-lg border-outline-variant text-body-sm font-mono">
                    @error('total_loss_db') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.distance') }} (km)</label>
                    <input type="number" step="0.001" name="distance_km" value="{{ old('distance_km', $record?->distance_km) }}"
                           class="w-full rounded-lg border-outline-variant text-body-sm font-mono">
                    @error('distance_km') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.fault_distance') }} (km)</label>
                    <input type="number" step="0.001" name="fault_distance_km" value="{{ old('fault_distance_km', $record?->fault_distance_km) }}"
                           class="w-full rounded-lg border-outline-variant text-body-sm font-mono">
                    @error('fault_distance_km') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.technician') }}</label>
                    <select name="measured_by" class="w-full rounded-lg border-outline-variant text-body-sm">
                        <option value="">{{ __('hfnms.current_user_default') }}</option>
                        @foreach ($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected(old('measured_by', $record?->measured_by) == $tech->id)>
                                {{ $tech->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.measured_at') }}</label>
                    <input type="datetime-local" name="measured_at"
                           value="{{ old('measured_at', $record?->measured_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-lg border-outline-variant text-body-sm">
                </div>
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.notes') }}</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-outline-variant text-body-sm">{{ old('notes', $record?->notes) }}</textarea>
                @error('notes') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.trace_file') }} (.sor)</label>
                @if ($isEdit && $record?->trace_file_path)
                    <p class="text-body-sm text-on-surface-variant mb-xs">
                        {{ __('hfnms.current_trace_file') }}:
                        <a href="{{ route('otdr-records.trace.download', $record) }}" class="text-primary font-semibold hover:underline">{{ basename($record->trace_file_path) }}</a>
                    </p>
                @endif
                <input type="file" name="trace_file" accept=".sor" @change="parseSor($event)"
                       class="w-full rounded-lg border-outline-variant text-body-sm file:mr-md file:py-sm file:px-md file:rounded file:border-0 file:bg-primary file:text-on-primary">
                <p class="text-[11px] text-on-surface-variant mt-xs">{{ __('hfnms.trace_file_hint') }}</p>
                <p x-show="parseMessage" x-text="parseMessage" class="text-body-sm mt-xs" :class="parseOk ? 'text-success' : 'text-on-surface-variant'"></p>
                @error('trace_file') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save_changes') : __('hfnms.save_otdr_record') }}
                </button>
                <a href="{{ $isEdit ? route('otdr-records.show', $record) : route('otdr-records.index') }}"
                   class="text-on-surface-variant text-body-sm hover:text-primary">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
