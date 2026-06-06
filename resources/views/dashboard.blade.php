<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-sm sm:gap-md w-full">
            <div class="min-w-0">
                <h2 class="font-semibold text-lg sm:text-headline-md text-on-surface truncate">{{ __('hfnms.dashboard_title') }}</h2>
                <p class="text-body-sm text-on-surface-variant line-clamp-2 sm:line-clamp-none">{{ __('hfnms.dashboard_subtitle') }}</p>
            </div>
            <p class="text-[10px] sm:text-[11px] text-on-surface-variant font-mono shrink-0">{{ now()->format('d M Y · H:i') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto space-y-lg pb-lg">

        {{-- KPI Cards --}}
        <section>
            <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.dashboard_kpi_section') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-md">
                <x-dashboard.metric-card accent="blue" icon="group" :label="__('hfnms.total_customers')"
                    :value="number_format($metrics['total_customers'])"
                    :subtitle="__('hfnms.active_count', ['count' => $metrics['active_customers']])" />
                <x-dashboard.metric-card accent="indigo" icon="lan" :label="__('hfnms.total_odp')"
                    :value="number_format($metrics['total_odp'])" subtitle="ODP" />
                <x-dashboard.metric-card accent="violet" icon="storage" :label="__('hfnms.total_odc')"
                    :value="number_format($metrics['total_odc'])" subtitle="ODC" />
                <x-dashboard.metric-card accent="cyan" icon="settings_input_component" :label="__('hfnms.total_cables')"
                    :value="number_format($metrics['total_cables'])"
                    :subtitle="__('hfnms.cable_length_km', ['km' => $metrics['cable_length_km']])" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-md mt-md">
                <x-dashboard.metric-card accent="amber" icon="memory" :label="__('hfnms.core_usage')"
                    :value="$metrics['core_usage_percent'].'%'" :progress="$metrics['core_usage_percent']" />
                <x-dashboard.metric-card accent="rose" icon="confirmation_number" :label="__('hfnms.open_tickets')"
                    :value="number_format($metrics['open_tickets'])" value-class="text-rose-600" />
                <x-dashboard.metric-card accent="emerald" icon="monitoring" :label="__('hfnms.network_health')"
                    :value="$metrics['network_health_percent'].'%'"
                    :subtitle="__('hfnms.fault_nodes', ['count' => $metrics['fault_nodes']])" />
                <x-dashboard.metric-card accent="orange" icon="build" :label="__('hfnms.nodes_maintenance')"
                    :value="number_format($metrics['nodes_in_maintenance'])"
                    :subtitle="__('hfnms.maintenance_in_progress_count', ['count' => $metrics['maintenance_in_progress']])" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-md mt-md">
                <x-dashboard.metric-card accent="sky" icon="signal_cellular_alt" :label="__('hfnms.otdr_readings')"
                    :value="number_format($metrics['otdr_readings'])"
                    :subtitle="__('hfnms.otdr_high_loss_count', ['count' => $metrics['otdr_high_loss']])" />
                <x-dashboard.metric-card accent="fuchsia" icon="event" :label="__('hfnms.maintenance_due')"
                    :value="number_format($metrics['maintenance_due'])"
                    :subtitle="__('hfnms.maintenance_due_hint')"
                    :value-class="$metrics['maintenance_due'] > 0 ? 'text-rose-600' : ''" />
                <x-dashboard.metric-card accent="teal" icon="inventory_2" :label="__('hfnms.core_available')"
                    :value="$metrics['core_available_percent'].'%'" :progress="$metrics['core_available_percent']" />
            </div>
        </section>

        {{-- Charts --}}
        <section>
            <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.dashboard_charts_section') }}</h3>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-md">
                <x-dashboard.chart-donut :title="__('hfnms.chart_core_status')" :segments="$coreStatusChart" :total="$coreStatusTotal" />
                <x-dashboard.chart-bar :title="__('hfnms.chart_node_distribution')" :items="$nodeDistributionChart" />
                <x-dashboard.chart-trend :title="__('hfnms.chart_ticket_trend')" :points="$ticketTrend" color="#ef4444" />
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-md mt-md">
                <x-dashboard.chart-bar :title="__('hfnms.chart_ticket_status')" :items="$ticketStatusChart" />
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg">
                    <h3 class="text-label-caps text-on-surface-variant mb-lg">{{ __('hfnms.dashboard_quick_health') }}</h3>
                    <dl class="grid grid-cols-2 gap-md text-body-sm">
                        <div class="p-md rounded-lg bg-emerald-50 border border-emerald-100">
                            <dt class="text-on-surface-variant text-[11px] uppercase">{{ __('hfnms.network_health') }}</dt>
                            <dd class="text-2xl font-bold text-emerald-700 mt-xs">{{ $metrics['network_health_percent'] }}%</dd>
                        </div>
                        <div class="p-md rounded-lg bg-blue-50 border border-blue-100">
                            <dt class="text-on-surface-variant text-[11px] uppercase">{{ __('hfnms.core_usage') }}</dt>
                            <dd class="text-2xl font-bold text-blue-700 mt-xs">{{ $metrics['core_usage_percent'] }}%</dd>
                        </div>
                        <div class="p-md rounded-lg bg-rose-50 border border-rose-100">
                            <dt class="text-on-surface-variant text-[11px] uppercase">{{ __('hfnms.open_tickets') }}</dt>
                            <dd class="text-2xl font-bold text-rose-700 mt-xs">{{ $metrics['open_tickets'] }}</dd>
                        </div>
                        <div class="p-md rounded-lg bg-amber-50 border border-amber-100">
                            <dt class="text-on-surface-variant text-[11px] uppercase">{{ __('hfnms.maintenance_due') }}</dt>
                            <dd class="text-2xl font-bold text-amber-700 mt-xs">{{ $metrics['maintenance_due'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        {{-- Alerts --}}
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center bg-gradient-to-r from-blue-50/50 to-transparent">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-blue-600 text-[20px]">notifications</span>
                        <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.dashboard_notifications') }}</h3>
                        @if ($unreadNotificationCount > 0)
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-xs rounded-full bg-rose-500 text-white text-[10px] font-bold">{{ $unreadNotificationCount }}</span>
                        @endif
                    </div>
                    <a href="{{ route('notifications.index') }}" class="text-body-sm text-primary font-semibold hover:underline">{{ __('hfnms.view_all') }}</a>
                </div>
                <ul class="divide-y divide-outline-variant max-h-[280px] overflow-y-auto">
                    @forelse ($recentNotifications as $notification)
                        <li class="px-lg py-md hover:bg-surface-container-low/50 {{ $notification->read_at ? '' : 'bg-blue-50/30' }}">
                            <a href="{{ route('notifications.read', $notification->id) }}" class="block">
                                <p class="text-body-sm {{ $notification->read_at ? 'text-on-surface-variant' : 'text-on-surface font-semibold' }}">{{ $notification->data['message'] ?? __('hfnms.notification_generic') }}</p>
                                <p class="text-[11px] text-on-surface-variant mt-xs">{{ $notification->created_at->diffForHumans() }}</p>
                            </a>
                        </li>
                    @empty
                        <li class="px-lg py-xl text-center text-on-surface-variant text-body-sm">{{ __('hfnms.no_notifications') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center bg-gradient-to-r from-amber-50/50 to-transparent">
                    <div class="flex items-center gap-sm">
                        <span class="material-symbols-outlined text-amber-600 text-[20px]">event_busy</span>
                        <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.dashboard_maintenance_alerts') }}</h3>
                        @if ($metrics['maintenance_overdue'] > 0)
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-xs rounded-full bg-rose-500 text-white text-[10px] font-bold">{{ $metrics['maintenance_overdue'] }}</span>
                        @endif
                    </div>
                    @can('maintenance.view')
                        <a href="{{ route('maintenance-schedules.index') }}" class="text-body-sm text-primary font-semibold hover:underline">{{ __('hfnms.view_all_maintenance') }}</a>
                    @endcan
                </div>
                <ul class="divide-y divide-outline-variant max-h-[280px] overflow-y-auto">
                    @forelse ($maintenanceAlerts as $alert)
                        @php $isOverdue = $alert->scheduled_at->isPast(); @endphp
                        <li class="px-lg py-md hover:bg-surface-container-low/50 {{ $isOverdue ? 'bg-rose-50/40' : '' }}">
                            @can('maintenance.view')
                                <a href="{{ route('maintenance-schedules.show', $alert) }}" class="block">
                            @else
                                <div>
                            @endcan
                                    <div class="flex justify-between items-start gap-md">
                                        <div>
                                            <p class="text-body-sm font-semibold {{ $isOverdue ? 'text-rose-600' : 'text-on-surface' }}">{{ Str::limit($alert->title, 45) }}</p>
                                            <p class="text-[11px] text-on-surface-variant mt-xs">{{ $alert->networkNode?->code ?? '—' }} · {{ $alert->assignee?->name ?? __('hfnms.unassigned') }}</p>
                                        </div>
                                        <span class="text-[11px] font-bold uppercase whitespace-nowrap {{ $isOverdue ? 'text-rose-600' : 'text-on-surface-variant' }}">{{ $isOverdue ? __('hfnms.overdue') : $alert->scheduled_at->diffForHumans() }}</span>
                                    </div>
                            @can('maintenance.view')
                                </a>
                            @else
                                </div>
                            @endcan
                        </li>
                    @empty
                        <li class="px-lg py-xl text-center text-on-surface-variant text-body-sm">{{ __('hfnms.no_maintenance_alerts') }}</li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- Tables --}}
        <section class="grid grid-cols-1 xl:grid-cols-3 gap-md">
            <div class="xl:col-span-2 bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                    <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.recent_incidents') }}</h3>
                    <a href="{{ route('trouble-tickets.index') }}" class="text-body-sm text-primary font-semibold hover:underline">{{ __('hfnms.view_all_tickets') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm">
                        <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                            <tr>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.ticket') }}</th>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.title') }}</th>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.priority') }}</th>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($incidents as $ticket)
                                <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                                    <td class="px-lg py-sm font-mono text-primary"><a href="{{ route('trouble-tickets.show', $ticket) }}" class="hover:underline">{{ $ticket->ticket_number }}</a></td>
                                    <td class="px-lg py-sm">{{ Str::limit($ticket->title, 40) }}</td>
                                    <td class="px-lg py-sm">
                                        <span class="px-sm py-xs rounded text-[11px] font-bold uppercase {{ in_array($ticket->priority, ['high', 'critical']) ? 'bg-rose-50 text-rose-600' : 'bg-surface-container text-on-surface-variant' }}">{{ __('hfnms.ticket_priority_'.$ticket->priority) }}</span>
                                    </td>
                                    <td class="px-lg py-sm">{{ __('hfnms.ticket_status_'.$ticket->status) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_incidents') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-lg shadow-sm">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.node_distribution') }}</h3>
                <ul class="space-y-sm">
                    @php $palette = ['text-blue-600','text-indigo-600','text-violet-600','text-cyan-600','text-teal-600','text-orange-600','text-rose-600']; $pi = 0; @endphp
                    @foreach ($nodeCounts as $type => $count)
                        @if ($count > 0)
                            <li class="flex justify-between items-center py-sm px-sm rounded-lg hover:bg-surface-container-low/60">
                                <span class="text-body-sm uppercase font-semibold {{ $palette[$pi % count($palette)] }}">{{ $type }}</span>
                                <span class="font-mono font-bold text-lg">{{ $count }}</span>
                            </li>
                            @php $pi++; @endphp
                        @endif
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                    <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.recent_otdr') }}</h3>
                    @can('cable.manage')
                        <a href="{{ route('otdr-records.index') }}" class="text-body-sm text-primary font-semibold hover:underline">{{ __('hfnms.view_all_otdr') }}</a>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm">
                        <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                            <tr>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.cable_core') }}</th>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.total_loss') }}</th>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.measured_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($otdrReadings as $reading)
                                <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                                    <td class="px-lg py-sm font-mono text-[12px]">
                                        @can('cable.manage')
                                            <a href="{{ route('otdr-records.show', $reading) }}" class="text-primary hover:underline">{{ $reading->cableCore?->cable?->code }} #{{ $reading->cableCore?->core_number }}</a>
                                        @else
                                            {{ $reading->cableCore?->cable?->code }} #{{ $reading->cableCore?->core_number }}
                                        @endcan
                                    </td>
                                    <td class="px-lg py-sm font-mono {{ ($reading->total_loss_db ?? 0) >= 0.35 ? 'text-rose-600 font-bold' : '' }}">{{ $reading->total_loss_db !== null ? number_format($reading->total_loss_db, 2).' dB' : '—' }}</td>
                                    <td class="px-lg py-sm">{{ $reading->measured_at?->format('d M Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_otdr_records') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="px-lg py-md border-b border-outline-variant flex justify-between items-center">
                    <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.upcoming_maintenance') }}</h3>
                    @can('maintenance.view')
                        <div class="flex items-center gap-sm">
                            <a href="{{ route('maintenance-schedules.calendar') }}" class="text-body-sm text-primary font-semibold hover:underline">{{ __('hfnms.calendar_view') }}</a>
                            <span class="text-outline-variant">·</span>
                            <a href="{{ route('maintenance-schedules.index') }}" class="text-body-sm text-primary font-semibold hover:underline">{{ __('hfnms.view_all_maintenance') }}</a>
                        </div>
                    @endcan
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-body-sm">
                        <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                            <tr>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.scheduled_at') }}</th>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.title') }}</th>
                                <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($upcomingMaintenance as $item)
                                <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                                    <td class="px-lg py-sm {{ $item->status === 'scheduled' && $item->scheduled_at->isPast() ? 'text-rose-600 font-semibold' : '' }}">{{ $item->scheduled_at->format('d M Y') }}</td>
                                    <td class="px-lg py-sm">
                                        @can('maintenance.view')
                                            <a href="{{ route('maintenance-schedules.show', $item) }}" class="hover:text-primary hover:underline">{{ Str::limit($item->title, 35) }}</a>
                                        @else
                                            {{ Str::limit($item->title, 35) }}
                                        @endcan
                                    </td>
                                    <td class="px-lg py-sm">{{ __('hfnms.maintenance_status_'.$item->status) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_maintenance_schedules') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div class="p-md bg-gradient-to-r from-primary-fixed/40 to-blue-50 rounded-xl border border-primary/10 flex flex-wrap items-center gap-md">
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.demo_hint') }}</p>
            <a href="{{ route('path-tracing.index', ['code' => 'HNT000001']) }}" class="inline-flex items-center gap-xs text-primary font-semibold text-body-sm hover:underline">
                <span class="material-symbols-outlined text-[18px]">route</span>
                HNT000001 — Budi Santoso
            </a>
            @can('cable.manage')
                <a href="{{ route('impact-analysis.index', ['mode' => 'node', 'node_code' => 'ODP-001']) }}" class="inline-flex items-center gap-xs text-primary font-semibold text-body-sm hover:underline">
                    <span class="material-symbols-outlined text-[18px]">bolt</span>
                    Impact ODP-001
                </a>
            @endcan
        </div>
    </div>
</x-app-layout>
