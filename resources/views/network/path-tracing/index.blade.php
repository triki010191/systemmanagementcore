<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-md flex-wrap">
            <div>
                <h2 class="font-semibold text-headline-md text-primary">{{ __('hfnms.path_tracing_title') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.path_tracing_subtitle') }}</p>
            </div>
            @if ($trace)
                <div class="flex items-center gap-sm ml-auto">
                    <span class="text-body-sm text-on-surface-variant">{{ __('hfnms.trace_id') }}:</span>
                    <span class="font-mono text-body-sm bg-surface-container-high px-sm py-xs rounded">{{ Str::limit($trace->traceId, 13, '') }}</span>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto space-y-lg" x-data="{
        query: @json($searchCode),
        results: [],
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const res = await fetch(`{{ route('path-tracing.search') }}?q=${encodeURIComponent(this.query)}`);
            this.results = await res.json();
        }
    }">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-lg">
            <div class="lg:col-span-1 bg-on-tertiary-fixed rounded-lg p-lg text-on-tertiary">
                <label class="text-label-caps text-tertiary-fixed-dim opacity-70 block mb-sm">{{ __('hfnms.search_customer') }}</label>
                <form method="GET" action="{{ route('path-tracing.index') }}" class="relative mb-md">
                    <input
                        type="text"
                        name="code"
                        value="{{ $searchCode }}"
                        x-model="query"
                        @input.debounce.300ms="search()"
                        class="w-full bg-on-tertiary-fixed-variant border-none rounded text-white text-sm focus:ring-2 focus:ring-primary-container py-sm px-md"
                        placeholder="{{ __('hfnms.search_placeholder') }}"
                    >
                    <span class="material-symbols-outlined absolute right-2 top-2 text-tertiary-fixed-dim text-sm">search</span>
                </form>

                <div class="space-y-2 max-h-[400px] overflow-y-auto custom-scrollbar">
                    <template x-for="item in results" :key="item.code">
                        <a :href="`{{ route('path-tracing.index') }}?code=${item.code}`"
                           class="block p-sm rounded cursor-pointer border transition-colors"
                           :class="item.code === '{{ $searchCode }}' ? 'bg-primary-container/20 border-primary-container/40' : 'border-transparent hover:bg-on-tertiary-fixed-variant'">
                            <div class="font-bold text-sm" x-text="item.code"></div>
                            <div class="text-xs opacity-80" x-text="item.name"></div>
                        </a>
                    </template>
                    @foreach ($customers as $customer)
                        <a href="{{ route('path-tracing.index', ['code' => $customer->code]) }}"
                           class="block p-sm rounded cursor-pointer border transition-colors {{ $searchCode === $customer->code ? 'bg-primary-container/20 border-primary-container/40' : 'border-transparent hover:bg-on-tertiary-fixed-variant' }}">
                            <div class="font-bold text-sm">{{ $customer->code }}</div>
                            <div class="text-xs opacity-80">{{ $customer->name }}</div>
                            @if ($customer->status === 'active')
                                <div class="flex items-center mt-xs">
                                    <span class="w-2 h-2 rounded-full bg-success mr-2"></span>
                                    <span class="text-[10px] text-success">Online</span>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-span-3 space-y-lg">
                @if ($trace)
                    <div class="flex justify-between items-end flex-wrap gap-md">
                        <div>
                            <h3 class="font-semibold text-headline-md">{{ __('hfnms.path_trace_for', ['name' => $trace->customer->name]) }}</h3>
                            <p class="text-on-surface-variant">{{ $trace->customer->code }} · {{ $trace->customer->onu_serial ?? '—' }}</p>
                        </div>
                        <div class="flex items-center gap-sm">
                            @if ($trace->isComplete)
                                <span class="inline-flex items-center gap-xs px-sm py-xs bg-green-50 text-success text-body-sm font-semibold rounded">
                                    <span class="w-2 h-2 rounded-full bg-success"></span>
                                    {{ __('hfnms.path_complete') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-xs px-sm py-xs bg-red-50 text-error text-body-sm font-semibold rounded">
                                    {{ __('hfnms.path_incomplete') }}
                                </span>
                            @endif
                            @can('report.export')
                                <a href="{{ route('path-tracing.export-pdf', ['code' => $trace->customer->code]) }}"
                                   class="inline-flex items-center gap-xs px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                                    <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                                    {{ __('hfnms.export_pdf') }}
                                </a>
                            @endcan
                        </div>
                    </div>

                    <section class="bg-surface-container-lowest border border-outline-variant p-xl rounded-lg overflow-x-auto">
                        <x-network.path-trace-diagram :hops="$trace->hops" :metrics="$trace->metrics" />
                    </section>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-md">
                        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                            <h4 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.hop_details') }}</h4>
                            <div class="space-y-sm">
                                @foreach ($trace->hops as $hop)
                                    <div class="flex justify-between items-center py-sm border-b border-outline-variant/50 text-body-sm">
                                        <div>
                                            <span class="font-semibold">{{ $hop['type_label'] }}</span>
                                            <span class="text-on-surface-variant ml-sm">{{ $hop['name'] }}</span>
                                        </div>
                                        <span class="font-mono text-primary">{{ $hop['code'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                            <h4 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.connection_specs') }}</h4>
                            <dl class="space-y-sm text-body-sm">
                                <div class="flex justify-between"><dt class="text-on-surface-variant">ONU Serial</dt><dd class="font-mono">{{ $trace->customer->onu_serial ?? '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-on-surface-variant">VLAN</dt><dd class="font-mono">{{ $trace->customer->vlan_tag ?? '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-on-surface-variant">IP</dt><dd class="font-mono">{{ $trace->customer->ip_address ?? '—' }}</dd></div>
                                <div class="flex justify-between"><dt class="text-on-surface-variant">TX Power</dt><dd class="font-mono">{{ $trace->metrics['tx_power_dbm'] ?? '—' }} dBm</dd></div>
                                @if (! $trace->isComplete)
                                    <div class="mt-md p-sm bg-red-50 rounded text-error text-body-sm">
                                        <strong>{{ __('hfnms.missing_segments') }}:</strong>
                                        {{ implode(', ', $trace->missingSegments) }}
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                @else
                    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-xl text-center">
                        @if ($traceError ?? false)
                            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $traceError }}</div>
                        @endif
                        <span class="material-symbols-outlined text-[48px] text-outline-variant mb-md">route</span>
                        <h3 class="font-semibold text-headline-md mb-sm">{{ __('hfnms.select_customer') }}</h3>
                        <p class="text-on-surface-variant text-body-sm max-w-md mx-auto">{{ __('hfnms.path_tracing_empty') }}</p>
                        @if ($customers->isNotEmpty())
                            <a href="{{ route('path-tracing.index', ['code' => $customers->first()->code]) }}" class="inline-flex items-center gap-sm mt-lg px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                                <span class="material-symbols-outlined text-[18px]">person_search</span>
                                {{ $customers->first()->code }} — {{ $customers->first()->name }}
                            </a>
                        @else
                            <a href="{{ route('network.assets.create', 'customers') }}" class="inline-flex items-center gap-sm mt-lg px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                {{ __('hfnms.add_first_customer') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-app-layout>
