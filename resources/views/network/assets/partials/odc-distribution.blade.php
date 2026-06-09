@php
    $dist = $odcDistribution ?? ['splitters' => [], 'route_summary' => ['inbound' => [], 'outbound' => []], 'inbound_cables' => [], 'child_odps' => [], 'sibling_odcs' => [], 'outbound_cables' => []];
    if (old('splitters')) {
        $dist['splitters'] = old('splitters');
    }
    $routeSummary = $dist['route_summary'] ?? ['inbound' => [], 'outbound' => []];
@endphp

<div class="mt-lg bg-surface-container-lowest border border-outline-variant rounded-lg p-lg"
     x-data="odcDistributionForm({
        optionsUrl: @js(route('network.odc.distribution.options', $asset->id)),
        updateUrl: @js(route('network.odc.distribution.update', $asset->id)),
        initial: @js($dist),
        ratios: @js(collect(\App\Enums\OdcSplitterRatio::cases())->map(fn ($r) => ['value' => $r->value, 'outputs' => $r->outputCount()])->values()),
        targetTypes: @js(collect(\App\Enums\OdcSplitterTargetType::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()])->values()),
        routeModes: @js(collect(\App\Enums\OdcSplitterRouteMode::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()])->values()),
     })"
     x-init="init()">
    <div class="flex items-start justify-between gap-md mb-md">
        <div>
            <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.odc_dist_title') }}</h3>
            <p class="text-body-sm text-on-surface-variant mt-xs">{{ __('hfnms.odc_dist_subtitle') }}</p>
        </div>
        <button type="button" @click="addSplitter()"
                class="px-md py-sm bg-primary-container text-on-primary rounded text-body-sm font-semibold hover:opacity-90 shrink-0">
            {{ __('hfnms.odc_dist_add_splitter') }}
        </button>
    </div>

    @if ($errors->has('distribution'))
        <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $errors->first('distribution') }}</div>
    @endif

    @php
        $distErrors = collect($errors->getMessages())->filter(fn ($_, $key) => str_starts_with((string) $key, 'splitters'));
    @endphp
    @if ($distErrors->isNotEmpty())
        <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">
            <p class="font-semibold mb-xs">{{ __('hfnms.form_validation_failed') }}</p>
            <ul class="list-disc pl-lg space-y-xs">
                @foreach ($distErrors->flatten() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-lg space-y-md">
        <div class="rounded-lg border border-outline-variant bg-surface-container-low p-md">
            <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.odc_route_summary_inbound') }}</p>
            @if (empty($routeSummary['inbound']))
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.odc_route_summary_empty_inbound') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm">
                        <thead>
                            <tr class="text-left text-on-surface-variant border-b border-outline-variant text-[11px] uppercase tracking-wide">
                                <th class="py-xs pr-sm">{{ __('hfnms.joint_code') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.joint_type') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.route_from') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.route_to') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.cable_code') }}</th>
                                <th class="py-xs">{{ __('hfnms.core_number') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($routeSummary['inbound'] as $row)
                                <tr class="border-b border-outline-variant/40">
                                    <td class="py-sm pr-sm font-mono font-semibold">{{ $row['joint_code'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm">{{ $row['joint_type'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm font-mono">{{ $row['source_code'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm font-mono">{{ $row['target_code'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm font-mono text-[12px]">{{ $row['cable_a_code'] ?? '—' }}@if (($row['cable_b_code'] ?? '') !== ($row['cable_a_code'] ?? '')) / {{ $row['cable_b_code'] }}@endif</td>
                                    <td class="py-sm font-mono font-semibold">{{ $row['core_number'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-primary/30 bg-primary/5 p-md">
            <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.odc_route_summary_outbound') }}</p>
            @if (empty($routeSummary['outbound']))
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.odc_route_summary_empty_outbound') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm">
                        <thead>
                            <tr class="text-left text-on-surface-variant border-b border-outline-variant text-[11px] uppercase tracking-wide">
                                <th class="py-xs pr-sm">{{ __('hfnms.port') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_splitter') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_target_type') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.route_to') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_route_mode') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_outbound_cable') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.core_number') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_downstream_cable') }}</th>
                                <th class="py-xs pr-sm">{{ __('hfnms.core_number') }}</th>
                                <th class="py-xs">{{ __('hfnms.joint_code') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($routeSummary['outbound'] as $row)
                                <tr class="border-b border-outline-variant/40">
                                    <td class="py-sm pr-sm font-mono font-semibold">P{{ $row['port'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm">
                                        <span class="font-mono text-[12px]">{{ $row['input_cable_code'] ?? '—' }} c{{ $row['input_core_number'] ?? '—' }}</span>
                                        <span class="text-on-surface-variant"> · {{ $row['split_ratio'] ?? '—' }}</span>
                                        @if (! empty($row['splitter_label']))
                                            <br><span class="text-[11px] text-on-surface-variant">{{ $row['splitter_label'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-sm pr-sm">{{ $row['target_type'] ?? ($row['joint_type'] ?? '—') }}</td>
                                    <td class="py-sm pr-sm font-mono font-semibold">{{ $row['target_code'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm text-[11px]">{{ $row['route_mode'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm font-mono">{{ $row['cable_code'] ?? ($row['cable_a_code'] ?? '—') }}</td>
                                    <td class="py-sm pr-sm font-mono font-semibold">{{ $row['core_number'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm font-mono text-[12px]">{{ $row['downstream_cable_code'] ?? '—' }}</td>
                                    <td class="py-sm pr-sm font-mono font-semibold">{{ $row['downstream_core_number'] ?? '—' }}</td>
                                    <td class="py-sm font-mono text-[12px]">{{ $row['joint_code'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <template x-if="splitters.length === 0">
        <p class="text-body-sm text-on-surface-variant py-md text-center border border-dashed border-outline-variant rounded-lg">
            {{ __('hfnms.odc_dist_empty') }}
        </p>
    </template>

    <div class="space-y-lg">
        <template x-for="(splitter, sIndex) in splitters" :key="splitter._key">
            <div class="border border-outline-variant rounded-lg p-md space-y-md bg-surface-container-low">
                <div class="flex items-center justify-between gap-sm">
                    <p class="text-body-sm font-semibold" x-text="'{{ __('hfnms.odc_dist_splitter') }} #' + (sIndex + 1)"></p>
                    <button type="button" @click="removeSplitter(sIndex)" class="text-error text-body-sm font-semibold hover:underline">
                        {{ __('hfnms.remove') }}
                    </button>
                </div>

                <div class="rounded-md bg-surface-container-lowest border border-outline-variant/60 px-md py-sm text-body-sm"
                     x-show="splitter.input_cable_id && splitter.input_core_number">
                    <span class="text-on-surface-variant">{{ __('hfnms.odc_dist_input_cable') }}:</span>
                    <span class="font-mono font-semibold" x-text="cableLabel(splitter.input_cable_id)"></span>
                    <span class="text-on-surface-variant">· {{ __('hfnms.odc_dist_input_core') }}</span>
                    <span class="font-mono font-semibold" x-text="splitter.input_core_number"></span>
                    <span class="text-on-surface-variant">· {{ __('hfnms.odc_dist_ratio') }}</span>
                    <span class="font-mono font-semibold" x-text="splitter.split_ratio"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-md">
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.odc_dist_label') }}</label>
                        <input type="text" x-model="splitter.label"
                               class="w-full rounded-lg border-outline-variant text-body-sm" placeholder="Splitter 1">
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.odc_dist_input_cable') }} *</label>
                        <select x-model.number="splitter.input_cable_id"
                                class="w-full rounded-lg border-outline-variant text-body-sm">
                            <option value="">{{ __('hfnms.select_cable') }}</option>
                            <template x-for="cable in inboundCables" :key="'s'+sIndex+'-in-'+cable.id">
                                <option :value="cable.id" x-text="cable.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.odc_dist_input_core') }} *</label>
                        <input type="number" min="1" max="999"
                               x-model.number="splitter.input_core_number"
                               class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.odc_dist_input_core_help') }}</p>
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.odc_dist_ratio') }} *</label>
                        <select x-model="splitter.split_ratio" @change="onRatioChange(splitter)"
                                class="w-full rounded-lg border-outline-variant text-body-sm">
                            <template x-for="ratio in ratios" :key="'s'+sIndex+'-ratio-'+ratio.value">
                                <option :value="ratio.value" x-text="ratio.value"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.odc_dist_outputs') }}</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-body-sm">
                            <thead>
                                <tr class="text-left text-on-surface-variant border-b border-outline-variant">
                                    <th class="py-xs pr-sm">{{ __('hfnms.port') }}</th>
                                    <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_target_type') }}</th>
                                    <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_target') }}</th>
                                    <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_route_mode') }}</th>
                                    <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_outbound_cable_odc') }}</th>
                                    <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_outbound_core') }}</th>
                                    <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_downstream_cable') }}</th>
                                    <th class="py-xs pr-sm">{{ __('hfnms.odc_dist_downstream_core') }}</th>
                                    <th class="py-xs">{{ __('hfnms.odc_route_summary_line') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="output in splitter.outputs" :key="splitter._key + '-port-' + output.output_port">
                                    <tr class="border-b border-outline-variant/50">
                                        <td class="py-sm pr-sm font-mono font-semibold" x-text="output.output_port"></td>
                                        <td class="py-sm pr-sm">
                                            <select x-model="output.target_type"
                                                    @change="onTargetTypeChange(output, $event)"
                                                    class="w-full min-w-[90px] rounded-lg border-outline-variant text-body-sm">
                                                <option value="">{{ '—' }}</option>
                                                <template x-for="tt in targetTypes" :key="splitter._key + '-p' + output.output_port + '-tt-' + tt.value">
                                                    <option :value="tt.value" x-text="tt.label"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="py-sm pr-sm">
                                            <select x-model.number="output.target_node_id"
                                                    @change="onTargetChange(output, $event)"
                                                    :disabled="!output.target_type"
                                                    class="w-full min-w-[140px] rounded-lg border-outline-variant text-body-sm">
                                                <option value="">{{ __('hfnms.select_node') }}</option>
                                                <template x-for="node in output.availableTargets" :key="splitter._key + '-p' + output.output_port + '-tgt-' + node.id">
                                                    <option :value="node.id" x-text="node.code + (node.name ? ' — ' + node.name : '')"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="py-sm pr-sm">
                                            <select x-model="output.route_mode"
                                                    @change="onRouteModeChange(output, $event)"
                                                    :disabled="!output.target_node_id"
                                                    class="w-full min-w-[150px] rounded-lg border-outline-variant text-body-sm">
                                                <template x-for="mode in routeModes" :key="splitter._key + '-p' + output.output_port + '-rm-' + mode.value">
                                                    <option :value="mode.value" x-text="mode.label"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="py-sm pr-sm">
                                            <select x-model.number="output.outbound_cable_id"
                                                    @change="onOutboundCableChange(output)"
                                                    :disabled="!output.target_node_id"
                                                    class="w-full min-w-[140px] rounded-lg border-outline-variant text-body-sm">
                                                <option value="">{{ __('hfnms.select_cable') }}</option>
                                                <template x-for="cable in output.availableOutboundCables" :key="splitter._key + '-p' + output.output_port + '-out-' + cable.id">
                                                    <option :value="cable.id" x-text="cable.label"></option>
                                                </template>
                                            </select>
                                            <p class="text-[10px] text-amber-700 mt-xs"
                                               x-show="output.target_node_id && output.availableOutboundCables.length === 0">
                                                {{ __('hfnms.odc_dist_no_cable_to_target') }}
                                            </p>
                                        </td>
                                        <td class="py-sm min-w-[130px]">
                                            <select x-model.number="output.outbound_core_number"
                                                    @focus="ensureCoresLoaded(output)"
                                                    :disabled="!output.outbound_cable_id"
                                                    class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                                                <option value="">{{ __('hfnms.select_core') }}</option>
                                                <template x-for="core in output.availableCoreOptions" :key="splitter._key + '-p' + output.output_port + '-oc-' + core.core_number">
                                                    <option :value="core.core_number" x-text="core.label"></option>
                                                </template>
                                            </select>
                                            <p class="text-[10px] text-outline mt-xs"
                                               x-show="output.outbound_cable_id && coresLoadingId === Number(output.outbound_cable_id)">
                                                {{ __('hfnms.odc_dist_cores_loading') }}
                                            </p>
                                            <p class="text-[10px] text-error mt-xs"
                                               x-show="output.outbound_cable_id && coresLoadingId !== Number(output.outbound_cable_id) && output.availableCoreOptions.length === 0">
                                                {{ __('hfnms.odc_dist_no_cores') }}
                                            </p>
                                        </td>
                                        <td class="py-sm pr-sm" x-show="isViaJoint(output)">
                                            <select x-model.number="output.downstream_cable_id"
                                                    @change="onDownstreamCableChange(output)"
                                                    class="w-full min-w-[140px] rounded-lg border-outline-variant text-body-sm">
                                                <option value="">{{ __('hfnms.select_cable') }}</option>
                                                <template x-for="cable in output.availableDownstreamCables" :key="splitter._key + '-p' + output.output_port + '-down-' + cable.id">
                                                    <option :value="cable.id" x-text="cable.label"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="py-sm min-w-[130px]" x-show="isViaJoint(output)">
                                            <select x-model.number="output.downstream_core_number"
                                                    @focus="ensureDownstreamCoresLoaded(output)"
                                                    :disabled="!output.downstream_cable_id"
                                                    class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                                                <option value="">{{ __('hfnms.select_core') }}</option>
                                                <template x-for="core in output.availableDownstreamCoreOptions" :key="splitter._key + '-p' + output.output_port + '-dc-' + core.core_number">
                                                    <option :value="core.core_number" x-text="core.label"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="py-sm pr-sm text-on-surface-variant text-[11px]" x-show="!isViaJoint(output)" colspan="2">
                                            <span class="italic">{{ __('hfnms.odc_dist_route_mode_direct') }}</span>
                                        </td>
                                        <td class="py-sm text-[12px] font-mono text-on-surface-variant min-w-[200px]">
                                            <span x-text="outputSummaryLine(splitter, output)" class="block leading-snug"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <form method="POST" :action="updateUrl" class="mt-lg pt-md border-t border-outline-variant" x-show="splitters.length > 0"
          @submit="syncDistributionFormFields($event)">
        @csrf
        @method('PUT')
        <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
            {{ __('hfnms.odc_dist_save') }}
        </button>
    </form>
</div>

<script>
function odcDistributionForm(config) {
    let keyCounter = 0;
    const nextKey = () => 'spl-' + (++keyCounter);

    const blankOutput = (port) => ({
        output_port: port,
        target_type: '',
        target_node_id: '',
        route_mode: 'direct',
        outbound_cable_id: '',
        outbound_core_number: '',
        downstream_cable_id: '',
        downstream_core_number: '',
        availableTargets: [],
        availableOutboundCables: [],
        availableDownstreamCables: [],
        availableCoreOptions: [],
        availableDownstreamCoreOptions: [],
    });

    const normalizeSplitter = (splitter) => {
        const ratio = config.ratios.find(r => r.value === splitter.split_ratio) || config.ratios[0];
        const outputs = [];
        for (let port = 1; port <= ratio.outputs; port++) {
            const existing = (splitter.outputs || []).find(o => Number(o.output_port) === port);
            outputs.push(existing ? { ...blankOutput(port), ...existing, output_port: port } : blankOutput(port));
        }
        return {
            _key: splitter._key || nextKey(),
            id: splitter.id || null,
            label: splitter.label || '',
            input_cable_id: splitter.input_cable_id || '',
            input_core_number: splitter.input_core_number || '',
            split_ratio: splitter.split_ratio || '1:4',
            outputs,
        };
    };

    return {
        optionsUrl: config.optionsUrl,
        updateUrl: config.updateUrl,
        ratios: config.ratios,
        targetTypes: config.targetTypes,
        routeModes: config.routeModes,
        inboundCables: config.initial.inbound_cables || [],
        outboundCables: config.initial.outbound_cables || [],
        childOdps: config.initial.child_odps || [],
        siblingOdcs: config.initial.sibling_odcs || [],
        splitters: (config.initial.splitters || []).map(s => normalizeSplitter(s)),
        coreCache: {},
        coresLoadingId: null,

        addSplitter() {
            const splitter = normalizeSplitter({
                split_ratio: '1:4',
                input_core_number: 1,
                outputs: [],
            });
            this.splitters.push(splitter);
            this.refreshAllOutputOptions(splitter);
        },

        removeSplitter(index) {
            this.splitters.splice(index, 1);
        },

        isViaJoint(output) {
            return (output?.route_mode || 'direct') === 'via_joint';
        },

        refreshTargetOptions(output) {
            if (! output) {
                return;
            }

            if (output.target_type === 'odp') {
                output.availableTargets = [...this.childOdps];
            } else if (output.target_type === 'odc') {
                output.availableTargets = [...this.siblingOdcs];
            } else {
                output.availableTargets = [];
            }
        },

        refreshCableOptions(output) {
            if (! output) {
                return;
            }

            if (this.isViaJoint(output)) {
                output.availableOutboundCables = [...this.outboundCables];
            } else if (! output.target_node_id) {
                output.availableOutboundCables = [];
            } else {
                const targetId = Number(output.target_node_id);
                output.availableOutboundCables = this.outboundCables.filter(
                    (cable) => cable.is_open_ended || Number(cable.other_node_id) === targetId,
                );
            }

            if (! output.target_node_id) {
                output.availableDownstreamCables = [];
            } else {
                const targetId = Number(output.target_node_id);
                output.availableDownstreamCables = this.outboundCables.filter(
                    (cable) => cable.is_open_ended || Number(cable.other_node_id) === targetId,
                );
            }
        },

        async refreshCoreOptions(output) {
            if (! output?.outbound_cable_id) {
                output.availableCoreOptions = [];
            } else {
                output.availableCoreOptions = await this.fetchCoresForCable(output.outbound_cable_id);
            }

            if (! output?.downstream_cable_id) {
                output.availableDownstreamCoreOptions = [];
            } else {
                output.availableDownstreamCoreOptions = await this.fetchCoresForCable(output.downstream_cable_id);
            }
        },

        refreshAllOutputOptions(splitter) {
            (splitter?.outputs || []).forEach((output) => {
                this.refreshTargetOptions(output);
                this.refreshCableOptions(output);
            });
        },

        onRatioChange(splitter) {
            const normalized = normalizeSplitter(splitter);
            splitter.outputs = normalized.outputs;
            this.refreshAllOutputOptions(splitter);
            splitter.outputs.forEach((output) => this.refreshCoreOptions(output));
        },

        onTargetTypeChange(output, event) {
            output.target_type = event?.target?.value || '';
            output.target_node_id = '';
            output.route_mode = 'direct';
            this.resetOutputCables(output);
            this.refreshTargetOptions(output);
            this.refreshCableOptions(output);
        },

        onTargetChange(output, event) {
            const raw = event?.target?.value ?? output.target_node_id;
            output.target_node_id = raw === '' ? '' : Number(raw);
            this.resetOutputCables(output);
            this.refreshCableOptions(output);
        },

        onRouteModeChange(output, event) {
            output.route_mode = event?.target?.value || 'direct';
            this.resetOutputCables(output);
            this.refreshCableOptions(output);
        },

        resetOutputCables(output) {
            output.outbound_cable_id = '';
            output.outbound_core_number = '';
            output.downstream_cable_id = '';
            output.downstream_core_number = '';
            output.availableCoreOptions = [];
            output.availableDownstreamCoreOptions = [];
        },

        coresForCable(cableId) {
            if (!cableId) {
                return [];
            }

            return this.coreCache[String(cableId)] || [];
        },

        maxCoreForCable(cableId) {
            const cable = [...this.inboundCables, ...this.outboundCables]
                .find((item) => Number(item.id) === Number(cableId));

            return cable?.core_count || 999;
        },

        async fetchCoresForCable(cableId) {
            const key = String(cableId);
            if (!cableId) {
                return [];
            }

            if (this.coreCache[key]) {
                return this.coreCache[key];
            }

            this.coresLoadingId = Number(cableId);

            try {
                const url = new URL(this.optionsUrl);
                url.searchParams.set('cable_id', cableId);
                const res = await fetch(url.toString(), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }

                const data = await res.json();
                this.coreCache[key] = data.cores || [];
            } catch (error) {
                this.coreCache[key] = [];
            } finally {
                this.coresLoadingId = null;
            }

            return this.coreCache[key];
        },

        async onOutboundCableChange(output) {
            output.outbound_core_number = '';
            await this.refreshCoreOptions(output);
        },

        async onDownstreamCableChange(output) {
            output.downstream_core_number = '';
            await this.refreshCoreOptions(output);
        },

        async ensureCoresLoaded(output) {
            if (! output?.outbound_cable_id) {
                return;
            }

            await this.refreshCoreOptions(output);
        },

        async ensureDownstreamCoresLoaded(output) {
            if (! output?.downstream_cable_id) {
                return;
            }

            await this.refreshCoreOptions(output);
        },

        cableLabel(cableId) {
            const cable = [...this.inboundCables, ...this.outboundCables].find((item) => Number(item.id) === Number(cableId));
            return cable?.code || '—';
        },

        nodeLabel(nodeId, targetType) {
            const pools = targetType === 'odc' ? this.siblingOdcs : this.childOdps;
            const node = pools.find((item) => Number(item.id) === Number(nodeId));
            return node?.code || '—';
        },

        outputSummaryLine(splitter, output) {
            if (! splitter || ! output || ! output.target_node_id || ! output.outbound_cable_id || ! output.outbound_core_number) {
                return '—';
            }

            if (this.isViaJoint(output) && (! output.downstream_cable_id || ! output.downstream_core_number)) {
                return '—';
            }

            const inputCable = this.cableLabel(splitter.input_cable_id);
            const outCable = this.cableLabel(output.outbound_cable_id);
            const downCable = this.cableLabel(output.downstream_cable_id);
            const target = this.nodeLabel(output.target_node_id, output.target_type);
            const type = (output.target_type || '').toUpperCase() || '?';

            let line;
            if (this.isViaJoint(output)) {
                line = `${inputCable} c${splitter.input_core_number || '?'} → P${output.output_port} → ${outCable} c${output.outbound_core_number} → Joint → ${downCable} c${output.downstream_core_number} → ${type} ${target}`;
            } else {
                line = `${inputCable} c${splitter.input_core_number || '?'} → P${output.output_port} → ${outCable} c${output.outbound_core_number} → ${type} ${target}`;
            }

            if (output.joint_code) {
                line += ` · ${output.joint_code}`;
            }

            return line;
        },

        syncDistributionFormFields(event) {
            const form = event.target;
            form.querySelectorAll('[data-odc-dist-field]').forEach((element) => element.remove());

            const appendField = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value === null || value === undefined ? '' : String(value);
                input.setAttribute('data-odc-dist-field', '1');
                form.appendChild(input);
            };

            this.splitters.forEach((splitter, sIndex) => {
                if (splitter.id) {
                    appendField(`splitters[${sIndex}][id]`, splitter.id);
                }

                appendField(`splitters[${sIndex}][label]`, splitter.label || '');
                appendField(`splitters[${sIndex}][input_cable_id]`, splitter.input_cable_id || '');
                appendField(`splitters[${sIndex}][input_core_number]`, splitter.input_core_number || '');
                appendField(`splitters[${sIndex}][split_ratio]`, splitter.split_ratio || '1:4');

                (splitter.outputs || []).forEach((output, oIndex) => {
                    if (output.id) {
                        appendField(`splitters[${sIndex}][outputs][${oIndex}][id]`, output.id);
                    }

                    appendField(`splitters[${sIndex}][outputs][${oIndex}][output_port]`, output.output_port);
                    appendField(`splitters[${sIndex}][outputs][${oIndex}][target_type]`, output.target_type || '');
                    appendField(`splitters[${sIndex}][outputs][${oIndex}][target_node_id]`, output.target_node_id || '');
                    appendField(`splitters[${sIndex}][outputs][${oIndex}][route_mode]`, output.route_mode || 'direct');
                    appendField(`splitters[${sIndex}][outputs][${oIndex}][outbound_cable_id]`, output.outbound_cable_id || '');
                    appendField(`splitters[${sIndex}][outputs][${oIndex}][outbound_core_number]`, output.outbound_core_number || '');
                    appendField(`splitters[${sIndex}][outputs][${oIndex}][downstream_cable_id]`, output.downstream_cable_id || '');
                    appendField(`splitters[${sIndex}][outputs][${oIndex}][downstream_core_number]`, output.downstream_core_number || '');
                });
            });
        },

        collectCableIdsForPreload() {
            const ids = new Set();

            this.inboundCables.forEach((cable) => ids.add(Number(cable.id)));
            this.outboundCables.forEach((cable) => ids.add(Number(cable.id)));
            this.splitters.forEach((splitter) => {
                if (splitter.input_cable_id) {
                    ids.add(Number(splitter.input_cable_id));
                }
                splitter.outputs.forEach((output) => {
                    if (output.outbound_cable_id) {
                        ids.add(Number(output.outbound_cable_id));
                    }
                    if (output.downstream_cable_id) {
                        ids.add(Number(output.downstream_cable_id));
                    }
                });
            });

            return [...ids].filter((id) => id > 0);
        },

        async init() {
            const cableIds = this.collectCableIdsForPreload();
            await Promise.all(cableIds.map((id) => this.fetchCoresForCable(id)));

            for (const splitter of this.splitters) {
                this.refreshAllOutputOptions(splitter);

                for (const output of splitter.outputs) {
                    if (output.outbound_core_number !== '' && output.outbound_core_number !== null) {
                        output.outbound_core_number = Number(output.outbound_core_number);
                    }
                    if (output.downstream_core_number !== '' && output.downstream_core_number !== null) {
                        output.downstream_core_number = Number(output.downstream_core_number);
                    }
                    if (! output.route_mode) {
                        output.route_mode = 'direct';
                    }

                    await this.refreshCoreOptions(output);
                }
            }
        },
    };
}
</script>
