<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.impact_analysis') }}</h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.impact_analysis_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto space-y-lg">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg" x-data="{ mode: @json($mode) }">
            <form method="GET" class="space-y-md">
                <div class="flex flex-wrap gap-md items-end">
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.analysis_mode') }}</label>
                        <select name="mode" x-model="mode" class="rounded-lg border-outline-variant text-body-sm">
                            <option value="node">{{ __('hfnms.by_network_node') }}</option>
                            <option value="core">{{ __('hfnms.by_fiber_core') }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                    <div x-show="mode === 'node'">
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.node_code') }}</label>
                        <input type="text" name="node_code" value="{{ $nodeCode }}"
                               placeholder="ODP-001, ODC-001, POP-JKT-01"
                               class="w-full rounded-lg border-outline-variant text-body-sm font-mono">
                    </div>
                    <div x-show="mode === 'core'" x-cloak>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_code') }}</label>
                        <select name="cable_code" class="w-full rounded-lg border-outline-variant text-body-sm font-mono">
                            <option value="">{{ __('hfnms.select_cable') }}</option>
                            @foreach ($cables as $cable)
                                <option value="{{ $cable->code }}" @selected($cableCode === $cable->code)>{{ $cable->code }} — {{ $cable->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div x-show="mode === 'core'" x-cloak>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.core_number') }}</label>
                        <input type="number" name="core_number" value="{{ $coreNumber }}" min="1" max="999"
                               class="w-full rounded-lg border-outline-variant text-body-sm font-mono">
                    </div>
                </div>

                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ __('hfnms.analyze_impact') }}
                </button>
            </form>
        </div>

        @if ($error)
            <div class="px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $error }}</div>
        @endif

        @if ($summary)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.affected_customers') }}</p>
                    <p class="text-headline-lg font-bold text-primary mt-xs">{{ $summary['affected_customer_count'] }}</p>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.active_count', ['count' => '']) }}</p>
                    <p class="text-headline-lg font-bold text-success mt-xs">{{ $summary['active_count'] }}</p>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.suspended_count') }}</p>
                    <p class="text-headline-lg font-bold text-orange-600 mt-xs">{{ $summary['suspended_count'] }}</p>
                </div>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                    <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.analysis_target') }}</p>
                    <p class="font-mono text-body-sm font-semibold mt-xs">
                        @if ($summary['target_type'] === 'node')
                            {{ $summary['node']->code }} ({{ $summary['node']->type->label() }})
                        @else
                            {{ $summary['core']->cable?->code }} #{{ $summary['core']->core_number }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-hidden">
                <table class="w-full text-body-sm">
                    <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                        <tr>
                            <th class="text-left px-lg py-sm">{{ __('hfnms.code') }}</th>
                            <th class="text-left px-lg py-sm">{{ __('hfnms.name') }}</th>
                            <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                            <th class="text-left px-lg py-sm">ODP</th>
                            <th class="text-right px-lg py-sm"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary['customers'] as $customer)
                            <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                                <td class="px-lg py-sm font-mono text-primary font-semibold">{{ $customer->code }}</td>
                                <td class="px-lg py-sm">{{ $customer->name }}</td>
                                <td class="px-lg py-sm">{{ $customer->status }}</td>
                                <td class="px-lg py-sm font-mono">{{ $customer->connection?->odp?->code ?? '—' }}</td>
                                <td class="px-lg py-sm text-right">
                                    <a href="{{ route('path-tracing.index', ['code' => $customer->code]) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.trace_path') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_affected_customers') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-xl text-center">
                <span class="material-symbols-outlined text-[48px] text-outline-variant mb-md">bolt</span>
                <h3 class="font-semibold text-headline-md mb-sm">{{ __('hfnms.impact_analysis_empty') }}</h3>
                <p class="text-on-surface-variant text-body-sm max-w-md mx-auto">{{ __('hfnms.impact_analysis_hint') }}</p>
                <a href="{{ route('impact-analysis.index', ['mode' => 'node', 'node_code' => 'ODP-001']) }}"
                   class="inline-flex items-center gap-sm mt-lg px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    Demo: ODP-001
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
