@php
    $isEdit = $connection !== null;
    $action = $isEdit
        ? route('customer-connections.update', $connection)
        : route('customer-connections.store');
    $initialOdp = old('odp_id', $connection?->odp_id ?? $suggestedOdpId ?? '');
    $initialOptions = $odpOptions ?? ['splitter_ports' => [], 'cable_cores' => [], 'taken_odp_ports' => [], 'odp' => null];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">
                {{ $isEdit ? __('hfnms.edit_connection') : __('hfnms.add_connection') }}
            </h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.connection_form_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[900px] mx-auto">
        @if ($errors->has('form'))
            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $errors->first('form') }}</div>
        @endif

        <form method="POST" action="{{ $action }}" class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg"
              x-data="{
                odpId: '{{ $initialOdp }}',
                exceptId: {{ $connection?->id ?? 'null' }},
                splitterPorts: @js($initialOptions['splitter_ports'] ?? []),
                cableCores: @js($initialOptions['cable_cores'] ?? []),
                takenOdpPorts: @js($initialOptions['taken_odp_ports'] ?? []),
                selectedSplitterPort: '{{ old('splitter_port_id', $connection?->splitter_port_id) }}',
                selectedCore: '{{ old('cable_core_id', $connection?->cable_core_id) }}',
                async loadOptions() {
                    if (!this.odpId) { this.splitterPorts = []; this.cableCores = []; this.takenOdpPorts = []; return; }
                    let url = '{{ url('/customer-connections/odp-options') }}/' + this.odpId;
                    if (this.exceptId) url += '?except=' + this.exceptId;
                    const data = await (await fetch(url)).json();
                    this.splitterPorts = data.splitter_ports || [];
                    this.cableCores = data.cable_cores || [];
                    this.takenOdpPorts = data.taken_odp_ports || [];
                }
              }"
              x-init="if (odpId && !splitterPorts.length) loadOptions()">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            @unless ($isEdit)
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.customer') }} *</label>
                    <select name="customer_id" required class="w-full rounded-lg border-outline-variant text-body-sm"
                            @if ($preselectedCustomer) disabled @endif>
                        <option value="">{{ __('hfnms.select_customer') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                @selected(old('customer_id', $preselectedCustomer?->id) == $customer->id)>
                                {{ $customer->code }} — {{ $customer->name }}
                                @if ($customer->networkNode?->parent)
                                    (Parent: {{ $customer->networkNode->parent->code }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @if ($preselectedCustomer)
                        <input type="hidden" name="customer_id" value="{{ $preselectedCustomer->id }}">
                    @endif
                    @error('customer_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="p-md bg-surface-container-low rounded-lg">
                    <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.customer') }}</p>
                    <p class="font-mono font-semibold text-primary mt-xs">{{ $connection->customer->code }}</p>
                    <p class="text-body-sm">{{ $connection->customer->name }}</p>
                </div>
            @endunless

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">ODP *</label>
                <select name="odp_id" required class="w-full rounded-lg border-outline-variant text-body-sm"
                        x-model="odpId" @change="loadOptions()">
                    <option value="">{{ __('hfnms.select_odp') }}</option>
                    @foreach ($odps as $odp)
                        <option value="{{ $odp->id }}" @selected((string) $initialOdp === (string) $odp->id)>
                            {{ $odp->code }} — {{ $odp->networkNode?->name }}
                            ({{ $odp->port_used }}/{{ $odp->port_capacity }} ports)
                        </option>
                    @endforeach
                </select>
                @error('odp_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.odp_port') }} *</label>
                    <input type="number" name="odp_port_number" min="1" max="255" required
                           value="{{ old('odp_port_number', $connection?->odp_port_number) }}"
                           class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                    <p class="text-[11px] text-outline mt-xs" x-show="takenOdpPorts.length" x-cloak>
                        {{ __('hfnms.taken_ports') }}: <span x-text="takenOdpPorts.join(', ')"></span>
                    </p>
                    @error('odp_port_number') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.drop_length') }}</label>
                    <input type="number" step="0.01" name="drop_length_m" min="0"
                           value="{{ old('drop_length_m', $connection?->drop_length_m) }}"
                           class="w-full rounded-lg border-outline-variant font-mono text-body-sm" placeholder="meter">
                    @error('drop_length_m') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.splitter_port') }}</label>
                    <select name="splitter_port_id" class="w-full rounded-lg border-outline-variant font-mono text-body-sm"
                            x-model="selectedSplitterPort">
                        <option value="">{{ __('hfnms.none_optional') }}</option>
                        <template x-for="port in splitterPorts" :key="port.id">
                            <option :value="port.id" :disabled="!port.available" x-text="port.label + (port.available ? '' : ' ({{ __('hfnms.in_use') }})')"></option>
                        </template>
                    </select>
                    @error('splitter_port_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_core') }}</label>
                    <select name="cable_core_id" class="w-full rounded-lg border-outline-variant text-body-sm"
                            x-model="selectedCore">
                        <option value="">{{ __('hfnms.none_optional') }}</option>
                        <template x-for="core in cableCores" :key="core.id">
                            <option :value="core.id" :disabled="!core.available" x-text="core.label + (core.available ? '' : ' ({{ __('hfnms.in_use') }})')"></option>
                        </template>
                    </select>
                    @error('cable_core_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.connected_at') }}</label>
                <input type="date" name="connected_at"
                       value="{{ old('connected_at', $connection?->connected_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                       class="w-full md:w-auto rounded-lg border-outline-variant text-body-sm">
            </div>

            <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save_changes') : __('hfnms.create_connection') }}
                </button>
                <a href="{{ route('customer-connections.index') }}" class="text-on-surface-variant hover:text-primary text-body-sm">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
