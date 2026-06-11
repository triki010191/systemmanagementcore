<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.maintenance_schedule') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.maintenance_schedule_subtitle') }}</p>
            </div>
            <div class="flex items-center gap-sm flex-wrap">
                <a href="{{ route('maintenance-schedules.calendar') }}"
                   class="inline-flex items-center gap-xs px-md py-sm border border-outline-variant rounded font-semibold text-body-sm hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-[18px]">calendar_month</span>
                    {{ __('hfnms.calendar_view') }}
                </a>
                @can('maintenance.manage')
                    <a href="{{ route('maintenance-schedules.create') }}"
                       class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        {{ __('hfnms.add_maintenance') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        @if (session('success'))
            <div class="mb-md px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <form method="GET" class="mb-md flex flex-wrap gap-sm items-center">
            <label class="text-body-sm text-on-surface-variant">{{ __('hfnms.filter_status') }}:</label>
            <select name="status" class="rounded-lg border-outline-variant text-body-sm" onchange="this.form.submit()">
                <option value="">{{ __('hfnms.all_statuses') }}</option>
                @foreach (['scheduled', 'in_progress', 'completed', 'cancelled'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('hfnms.maintenance_status_'.$s) }}</option>
                @endforeach
            </select>
        </form>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.scheduled_at') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.title') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.schedule_type') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.priority') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.network_node') }}</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        @php
                            $isOverdue = $schedule->status === 'scheduled' && $schedule->scheduled_at->isPast();
                            $priorityClass = match ($schedule->priority) {
                                'critical' => 'bg-red-100 text-red-700',
                                'high' => 'bg-orange-100 text-orange-700',
                                'medium' => 'bg-yellow-100 text-yellow-700',
                                default => 'bg-surface-container-high text-on-surface-variant',
                            };
                            $statusClass = match ($schedule->status) {
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-surface-container-high text-on-surface-variant',
                                default => $isOverdue ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50 {{ $isOverdue ? 'bg-red-50/30' : '' }}">
                            <td class="px-lg py-sm whitespace-nowrap {{ $isOverdue ? 'text-error font-semibold' : '' }}">
                                {{ $schedule->scheduled_at->format('d M Y H:i') }}
                                @if ($isOverdue)
                                    <span class="text-[10px] block font-bold uppercase">{{ __('hfnms.overdue') }}</span>
                                @endif
                            </td>
                            <td class="px-lg py-sm">{{ Str::limit($schedule->title, 40) }}</td>
                            <td class="px-lg py-sm text-on-surface-variant">{{ __('hfnms.maintenance_type_'.$schedule->schedule_type) }}</td>
                            <td class="px-lg py-sm">
                                <span class="inline-block px-sm py-xs rounded text-[11px] font-bold {{ $priorityClass }}">{{ __('hfnms.maintenance_priority_'.$schedule->priority) }}</span>
                            </td>
                            <td class="px-lg py-sm">
                                <span class="inline-block px-sm py-xs rounded text-[11px] font-bold {{ $statusClass }}">{{ __('hfnms.maintenance_status_'.$schedule->status) }}</span>
                            </td>
                            <td class="px-lg py-sm font-mono">{{ $schedule->networkNode?->code ?? '—' }}</td>
                            <td class="px-lg py-sm text-right space-x-sm whitespace-nowrap">
                                <a href="{{ route('maintenance-schedules.show', $schedule) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view') }}</a>
                                @can('maintenance.manage')
                                    <a href="{{ route('maintenance-schedules.edit', $schedule) }}" class="text-on-surface-variant hover:text-primary">{{ __('hfnms.edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_maintenance_schedules') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($schedules->hasPages())
            <div class="mt-md">{{ $schedules->links() }}</div>
        @endif
    </div>
</x-app-layout>
