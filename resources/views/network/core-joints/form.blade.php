@php
    use App\Enums\CoreJointType;

    $isEdit = $joint !== null;
    $action = $isEdit ? route('core-joints.update', $joint) : route('core-joints.store');

    $gpsValue = old('gps_coordinates');
    if (! $gpsValue && $joint?->latitude !== null && $joint?->longitude !== null) {
        $gpsValue = \App\Support\GpsCoordinateParser::format((float) $joint->latitude, (float) $joint->longitude);
    }

    $initialJointType = old('joint_type', $joint?->joint_type?->value ?? $jointType->value);
    $initialOtbId = old('otb_reference_id', $initialOtbId ?? $joint?->sourceNode?->parent_id ?? '');
    $initialSourceCableId = old('source_cable_id', $joint?->coreA?->cable_id ?? '');
    $initialSourceCoreId = old('fiber_core_a_id', $joint?->fiber_core_a_id ?? '');
    $initialTargetCableId = old('target_cable_id', $joint?->coreB?->cable_id ?? '');
    $initialTargetCoreId = old('fiber_core_b_id', $joint?->fiber_core_b_id ?? '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">
                {{ $isEdit ? __('hfnms.edit_joint') : __('hfnms.add_joint') }}
            </h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.joint_form_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[1100px] mx-auto">
        <div class="mb-md px-md py-sm bg-surface-container-low border border-outline-variant rounded-lg text-body-sm text-on-surface-variant">
            {{ __('hfnms.joint_hierarchy_hint') }}
        </div>

        <div class="mb-md px-md py-sm bg-amber-50 border border-amber-200 rounded-lg text-body-sm text-on-surface-variant">
            {{ __('hfnms.joint_planned_cables_hint') }}
        </div>

        @if ($errors->has('form'))
            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $errors->first('form') }}</div>
        @endif

        <form method="POST" action="{{ $action }}" class="space-y-lg"
              x-data="jointMapForm({
                optionsUrl: '{{ route('core-joints.options') }}',
                routeUrl: '{{ route('gis.route') }}',
                exceptId: {{ $joint?->id ?? 'null' }},
                initialJointType: '{{ $initialJointType }}',
                initialOtbId: '{{ $initialOtbId }}',
                initialSourceId: '{{ old('source_node_id', $joint?->source_node_id) }}',
                initialTargetId: '{{ old('target_node_id', $joint?->target_node_id) }}',
                initialCoreNumber: '{{ old('core_number', $joint?->core_number) }}',
                initialSourceCableId: '{{ $initialSourceCableId }}',
                initialSourceCoreId: '{{ $initialSourceCoreId }}',
                initialTargetCableId: '{{ $initialTargetCableId }}',
                initialTargetCoreId: '{{ $initialTargetCoreId }}',
                initialGps: @js($gpsValue),
                initialLat: @js(old('latitude', $joint?->latitude)),
                initialLng: @js(old('longitude', $joint?->longitude)),
              })">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="source_node_id" :value="sourceId">
            <input type="hidden" name="target_node_id" :value="targetId">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_code') }}</label>
                            <input type="text" name="code" value="{{ old('code', $joint?->code ?? $suggestedCode) }}"
                                   class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                            @error('code') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_type') }} *</label>
                            <select name="joint_type" required class="w-full rounded-lg border-outline-variant text-body-sm"
                                    x-model="jointType" @change="onJointTypeChange()">
                                @foreach (CoreJointType::cases() as $type)
                                    <option value="{{ $type->value }}" @selected($initialJointType === $type->value)>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('joint_type') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- OTB → ODC --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-md" x-show="jointType === 'otb_odc'" x-cloak>
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_otb_source') }}</label>
                            <select class="w-full rounded-lg border-outline-variant text-body-sm"
                                    x-model="sourceId" @change="onSourceChange()" :disabled="jointType !== 'otb_odc'">
                                <option value="">{{ __('hfnms.select_node') }}</option>
                                <template x-for="node in sources" :key="'s-' + node.id">
                                    <option :value="node.id" x-text="'[' + node.type.toUpperCase() + '] ' + node.code + ' — ' + node.name"></option>
                                </template>
                            </select>
                            @error('source_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_odc_target') }}</label>
                            <select class="w-full rounded-lg border-outline-variant text-body-sm"
                                    x-model="targetId" @change="onTargetChange()" :disabled="jointType !== 'otb_odc'">
                                <option value="">{{ __('hfnms.select_node') }}</option>
                                <template x-for="node in targets" :key="'t-' + node.id">
                                    <option :value="node.id" x-text="'[' + node.type.toUpperCase() + '] ' + node.code + ' — ' + node.name"></option>
                                </template>
                            </select>
                            @error('target_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- OTB → ODC → ODP (ODC–ODP joint) --}}
                    <div class="space-y-md" x-show="jointType === 'odc_odp'" x-cloak>
                        <p class="text-[11px] text-outline">{{ __('hfnms.joint_odc_odp_flow_hint') }}</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                            <div>
                                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_otb_upstream') }}</label>
                                <select class="w-full rounded-lg border-outline-variant text-body-sm"
                                        x-model="otbId" @change="onOtbChange()">
                                    <option value="">{{ __('hfnms.select_node') }}</option>
                                    <template x-for="node in otbs" :key="'otb-' + node.id">
                                        <option :value="node.id" x-text="'[' + node.type.toUpperCase() + '] ' + node.code + ' — ' + node.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_odc_source') }}</label>
                                <select class="w-full rounded-lg border-outline-variant text-body-sm"
                                        x-model="sourceId" @change="onOdcSourceChange()" :disabled="jointType !== 'odc_odp'">
                                    <option value="">{{ __('hfnms.select_node') }}</option>
                                    <template x-for="node in odcs" :key="'odc-' + node.id">
                                        <option :value="node.id" x-text="'[' + node.type.toUpperCase() + '] ' + node.code + ' — ' + node.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_odp_target') }}</label>
                                <select class="w-full rounded-lg border-outline-variant text-body-sm"
                                        x-model="targetId" @change="onTargetChange()" :disabled="jointType !== 'odc_odp'">
                                    <option value="">{{ __('hfnms.select_node') }}</option>
                                    <template x-for="node in targets" :key="'odp-' + node.id">
                                        <option :value="node.id" x-text="'[' + node.type.toUpperCase() + '] ' + node.code + ' — ' + node.name"></option>
                                    </template>
                                </select>
                                @error('target_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        @error('source_node_id') <p class="text-error text-body-sm">{{ $message }}</p> @enderror
                    </div>

                    <div class="rounded-lg border border-primary/30 bg-primary/5 p-md space-y-md">
                        <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.joint_core_splice_title') }}</p>
                        <p class="text-[11px] text-outline">{{ __('hfnms.joint_core_splice_hint') }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                            <div class="space-y-sm">
                                <p class="text-[11px] font-semibold uppercase text-on-surface-variant">{{ __('hfnms.joint_source_side') }}</p>
                                <select x-model="sourceCableId" @change="onSourceCableChange()"
                                        :disabled="!sourceId"
                                        class="w-full rounded-lg border-outline-variant text-body-sm">
                                    <option value="">{{ __('hfnms.select_cable') }}</option>
                                    <template x-for="cable in sourceCables" :key="'sc-'+cable.id">
                                        <option :value="cable.id" x-text="cable.label + (cable.is_planned ? ' (planned)' : '')"></option>
                                    </template>
                                </select>
                                <select x-model="sourceCoreId" @change="syncCoreFields()"
                                        :disabled="!sourceCableId"
                                        class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                                    <option value="">{{ __('hfnms.select_core') }}</option>
                                    <template x-for="core in sourceCores" :key="'scor-'+core.id">
                                        <option :value="core.id" x-text="core.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="space-y-sm">
                                <p class="text-[11px] font-semibold uppercase text-on-surface-variant">{{ __('hfnms.joint_target_side') }}</p>
                                <select x-model="targetCableId" @change="onTargetCableChange()"
                                        :disabled="!targetId"
                                        class="w-full rounded-lg border-outline-variant text-body-sm">
                                    <option value="">{{ __('hfnms.select_cable') }}</option>
                                    <template x-for="cable in targetCables" :key="'tc-'+cable.id">
                                        <option :value="cable.id" x-text="cable.label + (cable.is_planned ? ' (planned)' : '')"></option>
                                    </template>
                                </select>
                                <select x-model="targetCoreId" @change="syncCoreFields()"
                                        :disabled="!targetCableId"
                                        class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                                    <option value="">{{ __('hfnms.select_core') }}</option>
                                    <template x-for="core in targetCores" :key="'tcor-'+core.id">
                                        <option :value="core.id" x-text="core.label"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="fiber_core_a_id" :value="sourceCoreId">
                        <input type="hidden" name="fiber_core_b_id" :value="targetCoreId">
                        <input type="hidden" name="core_number" :value="coreNumber">

                        <p class="text-[11px] text-outline mt-xs" x-show="coresLoading">{{ __('hfnms.joint_cores_loading') }}</p>
                        <p class="text-[11px] text-amber-700 mt-xs" x-show="coresHint" x-text="coresHint"></p>
                        @error('fiber_core_a_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                        @error('fiber_core_b_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                        @error('core_number') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.joint_gps') }}</label>
                        <input type="text" name="gps_coordinates" x-model="gpsText" @change="parseGpsInput()"
                               placeholder="-6.312098, 105.999511"
                               class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                        <input type="hidden" name="latitude" :value="lat">
                        <input type="hidden" name="longitude" :value="lng">
                        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.joint_gps_help') }}</p>
                        @error('gps_coordinates') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.splice_loss') }} (dB)</label>
                            <input type="number" step="0.01" name="splice_loss_db" value="{{ old('splice_loss_db', $joint?->splice_loss_db) }}"
                                   class="w-full rounded-lg border-outline-variant text-body-sm">
                        </div>
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.spliced_at') }}</label>
                            <input type="datetime-local" name="spliced_at"
                                   value="{{ old('spliced_at', optional($joint?->spliced_at)->format('Y-m-d\TH:i')) }}"
                                   class="w-full rounded-lg border-outline-variant text-body-sm">
                        </div>
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }} *</label>
                            <select name="status" class="w-full rounded-lg border-outline-variant text-body-sm">
                                @foreach (['active', 'removed'] as $st)
                                    <option value="{{ $st }}" @selected(old('status', $joint?->status ?? 'active') === $st)>{{ ucfirst($st) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.notes') }}</label>
                        <textarea name="notes" rows="3" class="w-full rounded-lg border-outline-variant text-body-sm">{{ old('notes', $joint?->notes) }}</textarea>
                    </div>
                </div>

                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-md flex flex-col min-h-[420px]">
                    <div class="flex items-center justify-between gap-sm mb-sm">
                        <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.joint_map_preview') }}</p>
                        <span class="text-[11px] text-outline" x-text="mapCaption"></span>
                    </div>
                    <div id="joint-map" class="flex-1 min-h-[360px] rounded-lg border border-outline-variant overflow-hidden"></div>
                    <div class="mt-sm flex flex-wrap gap-sm text-[11px] text-on-surface-variant">
                        <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#455A64]"></span> OTB</span>
                        <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#37474F]"></span> ODC</span>
                        <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#1565C0]"></span> ODP</span>
                        <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#D84315]"></span> Join</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save_changes') : __('hfnms.create_joint') }}
                </button>
                <a href="{{ route('core-joints.index') }}" class="text-on-surface-variant hover:text-primary text-body-sm">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <script>
        function jointMapForm(config) {
            return {
                optionsUrl: config.optionsUrl,
                routeUrl: config.routeUrl,
                exceptId: config.exceptId,
                jointType: config.initialJointType || 'otb_odc',
                otbId: config.initialOtbId || '',
                sourceId: config.initialSourceId || '',
                targetId: config.initialTargetId || '',
                coreNumber: config.initialCoreNumber || '',
                sourceCableId: config.initialSourceCableId || '',
                sourceCoreId: config.initialSourceCoreId || '',
                targetCableId: config.initialTargetCableId || '',
                targetCoreId: config.initialTargetCoreId || '',
                sourceCables: [],
                targetCables: [],
                sourceCores: [],
                targetCores: [],
                sources: [],
                otbs: [],
                odcs: [],
                targets: [],
                cores: [],
                coresHint: '',
                coresLoading: false,
                maxCoreNumber: 999,
                gpsText: config.initialGps || '',
                lat: config.initialLat,
                lng: config.initialLng,
                map: null,
                jointMarker: null,
                routeLayer: null,
                nodeLayer: null,

                get mapCaption() {
                    return this.jointType === 'odc_odp'
                        ? 'OTB → ODC → Join → ODP'
                        : 'OTB → Join → ODC';
                },

                get coreInputPlaceholder() {
                    if (this.coresLoading) {
                        return @js(__('hfnms.joint_cores_loading'));
                    }

                    if (!this.sourceId || !this.targetId) {
                        return @js(__('hfnms.joint_select_nodes_first'));
                    }

                    if (this.maxCoreNumber && this.maxCoreNumber < 999) {
                        return @js(__('hfnms.joint_core_manual_placeholder')).replace(':max', this.maxCoreNumber);
                    }

                    return @js(__('hfnms.joint_core_manual_placeholder_open'));
                },

                async init() {
                    if (this.jointType === 'odc_odp') {
                        await this.loadOtbs();
                        if (this.otbId) await this.loadOdcs();
                        if (this.sourceId) await this.loadTargets();
                    } else {
                        await this.loadSources();
                        if (this.sourceId) await this.loadTargets();
                    }
                    if (this.sourceId) await this.loadSourceCables();
                    if (this.targetId) await this.loadTargetCables();
                    if (this.sourceCableId) await this.loadCoresForCable('source', this.sourceCableId);
                    if (this.targetCableId) await this.loadCoresForCable('target', this.targetCableId);
                    this.syncCoreFields();
                    this.$nextTick(() => this.initMap());
                },

                async loadSourceCables() {
                    if (!this.sourceId) {
                        this.sourceCables = [];
                        return;
                    }

                    const res = await fetch(`${this.optionsUrl}?cables_at_node=1&node_id=${this.sourceId}`, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    this.sourceCables = data.cables || [];
                },

                async loadTargetCables() {
                    if (!this.targetId) {
                        this.targetCables = [];
                        return;
                    }

                    const res = await fetch(`${this.optionsUrl}?cables_at_node=1&node_id=${this.targetId}`, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    this.targetCables = data.cables || [];
                },

                async loadCoresForCable(side, cableId) {
                    if (!cableId) {
                        this[side + 'Cores'] = [];
                        return;
                    }

                    const res = await fetch(`${this.optionsUrl}?cable_id=${cableId}`, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    this[side + 'Cores'] = data.cores || [];
                },

                async onSourceCableChange() {
                    this.sourceCoreId = '';
                    await this.loadCoresForCable('source', this.sourceCableId);
                    this.syncCoreFields();
                },

                async onTargetCableChange() {
                    this.targetCoreId = '';
                    await this.loadCoresForCable('target', this.targetCableId);
                    this.syncCoreFields();
                },

                syncCoreFields() {
                    const sourceCore = this.sourceCores.find((core) => String(core.id) === String(this.sourceCoreId));
                    this.coreNumber = sourceCore ? sourceCore.core_number : '';
                },

                async loadSources() {
                    const res = await fetch(`${this.optionsUrl}?joint_type=${this.jointType}`, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    this.sources = data.sources || [];
                },

                async loadOtbs() {
                    const res = await fetch(`${this.optionsUrl}?joint_type=odc_odp`, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    this.otbs = data.otbs || [];
                },

                async loadOdcs() {
                    if (!this.otbId) {
                        this.odcs = [];
                        return;
                    }
                    const q = `joint_type=odc_odp&odcs_only=1&otb_node_id=${this.otbId}`;
                    const res = await fetch(`${this.optionsUrl}?${q}`, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    this.odcs = data.sources || [];
                },

                async loadTargets() {
                    if (!this.sourceId) { this.targets = []; return; }
                    const q = `joint_type=${this.jointType}&source_node_id=${this.sourceId}&targets_only=1`;
                    const res = await fetch(`${this.optionsUrl}?${q}`, {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const data = await res.json();
                    this.targets = data.targets || [];
                },

                async loadCores() {
                    if (!this.sourceId || !this.targetId) {
                        this.cores = [];
                        this.coresHint = '';
                        this.maxCoreNumber = 999;
                        this.syncCoreDatalist();
                        return;
                    }

                    this.coresLoading = true;
                    this.coresHint = '';

                    let q = `joint_type=${this.jointType}&source_node_id=${this.sourceId}&target_node_id=${this.targetId}`;
                    if (this.exceptId) q += `&except=${this.exceptId}`;

                    try {
                        const res = await fetch(`${this.optionsUrl}?${q}`, {
                            credentials: 'same-origin',
                            headers: { Accept: 'application/json' },
                        });

                        if (!res.ok) {
                            throw new Error('HTTP ' + res.status);
                        }

                        const data = await res.json();
                        this.cores = data.cores || [];
                        this.coresHint = data.hint || data.error || '';
                        this.maxCoreNumber = data.max_core_number || 999;
                    } catch (error) {
                        this.cores = [];
                        this.coresHint = @js(__('hfnms.joint_cores_load_failed'));
                    } finally {
                        this.coresLoading = false;
                        this.syncCoreDatalist();
                    }
                },

                syncCoreDatalist() {
                    const datalist = document.getElementById('joint-core-options');
                    if (!datalist) {
                        return;
                    }

                    datalist.innerHTML = '';

                    this.cores.forEach((core) => {
                        const option = document.createElement('option');
                        option.value = String(core.core_number);
                        option.label = core.label || `Core ${core.core_number}`;
                        datalist.appendChild(option);
                    });
                },

                async onJointTypeChange() {
                    this.otbId = '';
                    this.sourceId = '';
                    this.targetId = '';
                    this.coreNumber = '';
                    this.cores = [];
                    this.coresHint = '';
                    this.sources = [];
                    this.otbs = [];
                    this.odcs = [];
                    this.targets = [];
                    if (this.jointType === 'odc_odp') {
                        await this.loadOtbs();
                    } else {
                        await this.loadSources();
                    }
                    this.refreshMap();
                },

                async onOtbChange() {
                    this.sourceId = '';
                    this.targetId = '';
                    this.coreNumber = '';
                    this.cores = [];
                    this.coresHint = '';
                    this.targets = [];
                    await this.loadOdcs();
                    const node = this.otbs.find(n => String(n.id) === String(this.otbId));
                    if (node?.latitude) this.setPosition(node.latitude, node.longitude, true);
                    this.refreshMap();
                },

                async onOdcSourceChange() {
                    this.targetId = '';
                    this.coreNumber = '';
                    this.sourceCableId = '';
                    this.sourceCoreId = '';
                    this.targetCableId = '';
                    this.targetCoreId = '';
                    this.cores = [];
                    this.coresHint = '';
                    await this.loadSourceCables();
                    await this.loadTargets();
                    const node = this.odcs.find(n => String(n.id) === String(this.sourceId));
                    if (node?.latitude) this.setPosition(node.latitude, node.longitude, true);
                    this.refreshMap();
                },

                async onSourceChange() {
                    this.targetId = '';
                    this.coreNumber = '';
                    this.sourceCableId = '';
                    this.sourceCoreId = '';
                    this.targetCableId = '';
                    this.targetCoreId = '';
                    this.cores = [];
                    this.coresHint = '';
                    await this.loadSourceCables();
                    await this.loadTargets();
                    const node = this.sources.find(n => String(n.id) === String(this.sourceId));
                    if (node?.latitude) this.setPosition(node.latitude, node.longitude, true);
                    this.refreshMap();
                },

                async onTargetChange() {
                    this.sourceCableId = '';
                    this.sourceCoreId = '';
                    this.targetCableId = '';
                    this.targetCoreId = '';
                    this.coreNumber = '';
                    await this.loadSourceCables();
                    await this.loadTargetCables();
                    const node = this.targets.find(n => String(n.id) === String(this.targetId));
                    if (node?.latitude) this.setPosition(node.latitude, node.longitude, true);
                    this.refreshMap();
                },

                initMap() {
                    const defaultLat = this.lat || -6.176;
                    const defaultLng = this.lng || 106.866;
                    this.map = L.map('joint-map', { zoomControl: true }).setView([defaultLat, defaultLng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(this.map);
                    this.routeLayer = L.layerGroup().addTo(this.map);
                    this.nodeLayer = L.layerGroup().addTo(this.map);
                    this.map.on('click', (e) => this.setPosition(e.latlng.lat, e.latlng.lng));
                    if (this.lat && this.lng) this.placeJointMarker(this.lat, this.lng);
                    this.refreshMap();
                    setTimeout(() => this.map.invalidateSize(), 200);
                },

                setPosition(lat, lng, fly = false) {
                    this.lat = Number(Number(lat).toFixed(8));
                    this.lng = Number(Number(lng).toFixed(8));
                    this.gpsText = `${this.lat}, ${this.lng}`;
                    this.placeJointMarker(this.lat, this.lng);
                    this.refreshMap();
                    if (fly) this.map.setView([this.lat, this.lng], Math.max(this.map.getZoom(), 16));
                },

                parseGpsInput() {
                    const parts = this.gpsText.replace(/;/g, ',').split(',').map(p => p.trim()).filter(Boolean);
                    if (parts.length >= 2) {
                        const lat = parseFloat(parts[0]);
                        const lng = parseFloat(parts[1]);
                        if (!Number.isNaN(lat) && !Number.isNaN(lng)) this.setPosition(lat, lng);
                    }
                },

                placeJointMarker(lat, lng) {
                    if (this.jointMarker) {
                        this.jointMarker.setLatLng([lat, lng]);
                        return;
                    }
                    const icon = L.divIcon({
                        className: '',
                        html: '<div style="width:18px;height:18px;border-radius:50%;background:#D84315;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>',
                        iconSize: [18, 18], iconAnchor: [9, 9],
                    });
                    this.jointMarker = L.marker([lat, lng], { draggable: true, icon }).addTo(this.map);
                    this.jointMarker.on('dragend', () => {
                        const pos = this.jointMarker.getLatLng();
                        this.setPosition(pos.lat, pos.lng, false);
                    });
                },

                nodeColor(type) {
                    return { otb: '#455A64', odc: '#37474F', odp: '#1565C0' }[type] || '#546E7A';
                },

                refreshMap() {
                    if (!this.map) return;
                    this.routeLayer.clearLayers();
                    this.nodeLayer.clearLayers();

                    const waypoints = [];

                    if (this.jointType === 'odc_odp') {
                        const otb = this.otbs.find(n => String(n.id) === String(this.otbId));
                        const odc = this.odcs.find(n => String(n.id) === String(this.sourceId));
                        const odp = this.targets.find(n => String(n.id) === String(this.targetId));

                        [otb, odc, odp].forEach((node) => {
                            if (!node?.latitude) return;
                            L.circleMarker([node.latitude, node.longitude], {
                                radius: 9,
                                color: this.nodeColor(node.type),
                                fillColor: this.nodeColor(node.type),
                                fillOpacity: 0.9,
                                weight: 2,
                            }).addTo(this.nodeLayer).bindPopup(node.code);
                        });

                        if (otb?.latitude) waypoints.push({ lat: otb.latitude, lng: otb.longitude });
                        if (odc?.latitude) waypoints.push({ lat: odc.latitude, lng: odc.longitude });
                        if (this.lat && this.lng) waypoints.push({ lat: this.lat, lng: this.lng });
                        if (odp?.latitude) waypoints.push({ lat: odp.latitude, lng: odp.longitude });
                    } else {
                        const source = this.sources.find(n => String(n.id) === String(this.sourceId));
                        const target = this.targets.find(n => String(n.id) === String(this.targetId));

                        if (source?.latitude) {
                            L.circleMarker([source.latitude, source.longitude], {
                                radius: 9,
                                color: this.nodeColor(source.type || 'otb'),
                                fillColor: this.nodeColor(source.type || 'otb'),
                                fillOpacity: 0.9,
                                weight: 2,
                            }).addTo(this.nodeLayer).bindPopup(source.code);
                            waypoints.push({ lat: source.latitude, lng: source.longitude });
                        }

                        if (this.lat && this.lng) {
                            waypoints.push({ lat: this.lat, lng: this.lng });
                        }

                        if (target?.latitude) {
                            L.circleMarker([target.latitude, target.longitude], {
                                radius: 9,
                                color: this.nodeColor(target.type || 'odc'),
                                fillColor: this.nodeColor(target.type || 'odc'),
                                fillOpacity: 0.9,
                                weight: 2,
                            }).addTo(this.nodeLayer).bindPopup(target.code);
                            waypoints.push({ lat: target.latitude, lng: target.longitude });
                        }
                    }

                    if (this.lat && this.lng) {
                        if (!this.jointMarker) this.placeJointMarker(this.lat, this.lng);
                    }

                    if (waypoints.length >= 2) {
                        this.drawRoute(waypoints);
                    }
                },

                async drawRoute(waypoints) {
                    let path = waypoints.map(point => [point.lat, point.lng]);

                    if (this.routeUrl && waypoints.length >= 2) {
                        const params = new URLSearchParams();
                        waypoints.forEach((point, index) => {
                            params.append(`waypoints[${index}][lat]`, point.lat);
                            params.append(`waypoints[${index}][lng]`, point.lng);
                        });

                        try {
                            const response = await fetch(`${this.routeUrl}?${params.toString()}`, { credentials: 'same-origin' });
                            if (response.ok) {
                                const data = await response.json();
                                if (Array.isArray(data.path) && data.path.length >= 2) {
                                    path = data.path;
                                }
                            }
                        } catch (error) {
                            // fallback to straight segments
                        }
                    }

                    L.polyline(path, {
                        color: this.jointType === 'odc_odp' ? '#E65100' : '#1565C0',
                        weight: 4,
                        dashArray: '8,6',
                    }).addTo(this.routeLayer);

                    this.map.fitBounds(path, { padding: [40, 40], maxZoom: 17 });
                },
            };
        }
    </script>
</x-app-layout>
