@php
    $health = $metrics['network_health_percent'];
    $healthStatus = $health >= 90 ? 'healthy' : ($health >= 70 ? 'warning' : 'critical');
    $openTickets = $metrics['open_tickets'];
    $ticketStatus = $openTickets === 0 ? 'healthy' : ($openTickets <= 5 ? 'warning' : 'critical');
    $fiberUsage = $metrics['core_usage_percent'];
    $fiberStatus = $fiberUsage <= 75 ? 'primary' : ($fiberUsage <= 90 ? 'warning' : 'critical');
    $upcomingCount = $metrics['maintenance_due'] + $metrics['maintenance_in_progress'];
    $maintenanceStatus = $metrics['maintenance_overdue'] > 0 ? 'critical' : ($upcomingCount > 0 ? 'warning' : 'healthy');
    $hasCoreChart = $coreStatusTotal > 0;
    $infraCounts = collect($nodeCounts)->only(['pop', 'olt', 'otb', 'odc', 'odp'])->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div
            class="flex flex-wrap items-center justify-between gap-2 w-full"
            x-data="{
                now: new Date(),
                init() {
                    setInterval(() => { this.now = new Date() }, 1000);
                },
                formatted() {
                    return this.now.toLocaleString('{{ app()->getLocale() === 'id' ? 'id-ID' : 'en-GB' }}', {
                        weekday: 'short',
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    });
                }
            }"
        >
            <div class="min-w-0">
                <h2 class="font-semibold text-base sm:text-lg text-on-surface truncate">{{ __('hfnms.dashboard_title') }}</h2>
                <p class="text-[11px] text-on-surface-variant hidden sm:block">{{ __('hfnms.dashboard_subtitle') }}</p>
            </div>
            <p class="text-[11px] text-on-surface-variant font-mono shrink-0 tabular-nums" x-text="formatted()"></p>
        </div>
    </x-slot>

    <div class="dashboard-noc max-w-[1600px] mx-auto flex flex-col gap-3 pb-2">

        {{-- ROW 1: Main KPI Cards --}}
        <section class="grid grid-cols-2 xl:grid-cols-4 gap-3">
            <x-dashboard.metric-card
                accent="emerald"
                icon="monitoring"
                :label="__('hfnms.network_health')"
                :value="$health.'%'"
                :subtitle="__('hfnms.fault_nodes', ['count' => $metrics['fault_nodes']])"
                :status="$healthStatus"
            />
            <x-dashboard.metric-card
                accent="blue"
                icon="memory"
                :label="__('hfnms.core_usage')"
                :value="$fiberUsage.'%'"
                :progress="$fiberUsage"
                :status="$fiberStatus"
            />
            <x-dashboard.metric-card
                accent="rose"
                icon="confirmation_number"
                :label="__('hfnms.open_tickets')"
                :value="number_format($openTickets)"
                :subtitle="$metrics['maintenance_overdue'] > 0 ? __('hfnms.maintenance_overdue_count', ['count' => $metrics['maintenance_overdue']]) : null"
                :status="$ticketStatus"
            />
            <x-dashboard.metric-card
                accent="amber"
                icon="event"
                :label="__('hfnms.upcoming_maintenance')"
                :value="number_format($upcomingCount)"
                :subtitle="__('hfnms.maintenance_in_progress_count', ['count' => $metrics['maintenance_in_progress']])"
                :status="$maintenanceStatus"
            />
        </section>

        {{-- ROW 2: Analytics --}}
        <section @class([
            'grid grid-cols-1 gap-3',
            'lg:grid-cols-2' => $hasCoreChart,
        ])>
            @if ($hasCoreChart)
                <x-dashboard.chart-donut
                    :title="__('hfnms.chart_core_status')"
                    :segments="$coreStatusChart"
                    :total="$coreStatusTotal"
                />
            @endif
            <x-dashboard.chart-trend
                :title="__('hfnms.chart_incident_trend')"
                :points="$ticketTrend"
            />
        </section>

        {{-- ROW 3: Operations --}}
        @if ($incidents->isNotEmpty() || $upcomingMaintenance->isNotEmpty())
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                @if ($incidents->isNotEmpty())
                    <x-dashboard.operations-panel
                        :title="__('hfnms.recent_incidents')"
                        icon="report"
                        :view-all-route="route('trouble-tickets.index')"
                        :view-all-label="__('hfnms.view_all_tickets')"
                    >
                        <ul class="divide-y divide-outline-variant/50">
                            @foreach ($incidents as $ticket)
                                @php
                                    $isCritical = in_array($ticket->priority, ['high', 'critical'], true);
                                    $priorityClass = $isCritical
                                        ? 'bg-red-50 text-red-600 border-red-100'
                                        : 'bg-surface-container-low text-on-surface-variant border-outline-variant/60';
                                @endphp
                                <li class="px-4 py-2.5 hover:bg-surface-container-low/40 transition-colors">
                                    <a href="{{ route('trouble-tickets.show', $ticket) }}" class="flex items-center gap-3">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase border {{ $priorityClass }} shrink-0">
                                            {{ __('hfnms.ticket_priority_'.$ticket->priority) }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-body-sm font-medium text-on-surface truncate">
                                                <span class="font-mono text-primary">{{ $ticket->ticket_number }}</span>
                                                · {{ $ticket->title }}
                                            </p>
                                            <p class="text-[10px] text-on-surface-variant mt-0.5">
                                                {{ __('hfnms.ticket_status_'.$ticket->status) }}
                                                · {{ $ticket->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-dashboard.operations-panel>
                @endif

                @if ($upcomingMaintenance->isNotEmpty())
                    <x-dashboard.operations-panel
                        :title="__('hfnms.upcoming_maintenance')"
                        icon="build"
                        :view-all-route="auth()->user()->can('maintenance.view') ? route('maintenance-schedules.index') : null"
                        :view-all-label="auth()->user()->can('maintenance.view') ? __('hfnms.view_all_maintenance') : null"
                    >
                        <ul class="divide-y divide-outline-variant/50">
                            @foreach ($upcomingMaintenance as $item)
                                @php
                                    $isOverdue = $item->status === 'scheduled' && $item->scheduled_at->isPast();
                                    $statusClass = $isOverdue ? 'text-red-600' : ($item->status === 'in_progress' ? 'text-amber-600' : 'text-on-surface-variant');
                                @endphp
                                <li class="px-4 py-2.5 hover:bg-surface-container-low/40 transition-colors">
                                    @can('maintenance.view')
                                        <a href="{{ route('maintenance-schedules.show', $item) }}" class="flex items-start justify-between gap-3">
                                    @else
                                        <div class="flex items-start justify-between gap-3">
                                    @endcan
                                        <div class="min-w-0 flex-1">
                                            <p class="text-body-sm font-medium text-on-surface truncate {{ $isOverdue ? 'text-red-600' : '' }}">
                                                {{ $item->title }}
                                            </p>
                                            <p class="text-[10px] text-on-surface-variant mt-0.5">
                                                {{ $item->networkNode?->code ?? '—' }}
                                                · {{ $item->assignee?->name ?? __('hfnms.unassigned') }}
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <p class="text-[11px] font-mono font-semibold {{ $statusClass }}">
                                                {{ $item->scheduled_at->format('d M') }}
                                            </p>
                                            <p class="text-[10px] {{ $statusClass }}">
                                                {{ $isOverdue ? __('hfnms.overdue') : __('hfnms.maintenance_status_'.$item->status) }}
                                            </p>
                                        </div>
                                    @can('maintenance.view')
                                        </a>
                                    @else
                                        </div>
                                    @endcan
                                </li>
                            @endforeach
                        </ul>
                    </x-dashboard.operations-panel>
                @endif
            </section>
        @endif

        {{-- ROW 4: Infrastructure Summary --}}
        <x-dashboard.infra-badges :counts="$infraCounts" />

    </div>
</x-app-layout>
