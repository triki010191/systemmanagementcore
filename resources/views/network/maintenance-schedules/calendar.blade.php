<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.maintenance_calendar') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ $monthLabel }}</p>
            </div>
            <div class="flex items-center gap-sm flex-wrap">
                <a href="{{ route('maintenance-schedules.index') }}"
                   class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                    {{ __('hfnms.list_view') }}
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

    <div class="max-w-[1200px] mx-auto">
        <div class="flex items-center justify-between mb-md">
            <a href="{{ route('maintenance-schedules.calendar', ['month' => $prevMonth]) }}"
               class="inline-flex items-center gap-xs px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
                {{ __('hfnms.prev_month') }}
            </a>
            <a href="{{ route('maintenance-schedules.calendar', ['month' => $nextMonth]) }}"
               class="inline-flex items-center gap-xs px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                {{ __('hfnms.next_month') }}
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </a>
        </div>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-hidden">
            <div class="grid grid-cols-7 bg-surface-container-low text-label-caps text-on-surface-variant text-center">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                    <div class="py-sm border-b border-outline-variant">{{ $dayName }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @for ($blank = 1; $blank < $startWeekday; $blank++)
                    <div class="min-h-[100px] border-b border-r border-outline-variant/50 bg-surface-container-low/30"></div>
                @endfor

                @for ($day = 1; $day <= $daysInMonth; $day++)
                    @php
                        $dayEvents = $eventsByDay->get((string) $day, collect());
                        $isToday = now()->format('Y-n-j') === "{$year}-{$month}-{$day}";
                    @endphp
                    <div class="min-h-[100px] border-b border-r border-outline-variant/50 p-xs {{ $isToday ? 'bg-primary-fixed/20' : '' }}">
                        <div class="text-body-sm font-semibold mb-xs {{ $isToday ? 'text-primary' : 'text-on-surface-variant' }}">{{ $day }}</div>
                        <div class="space-y-1">
                            @foreach ($dayEvents->take(3) as $event)
                                @php
                                    $color = match ($event->status) {
                                        'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'completed' => 'bg-green-100 text-green-800 border-green-200',
                                        'cancelled' => 'bg-surface-container-high text-on-surface-variant border-outline-variant',
                                        default => $event->scheduled_at->isPast() ? 'bg-red-100 text-red-800 border-red-200' : 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    };
                                @endphp
                                <a href="{{ route('maintenance-schedules.show', $event) }}"
                                   class="block text-[10px] px-1 py-0.5 rounded border truncate {{ $color }}"
                                   title="{{ $event->title }}">
                                    {{ $event->scheduled_at->format('H:i') }} {{ Str::limit($event->title, 18) }}
                                </a>
                            @endforeach
                            @if ($dayEvents->count() > 3)
                                <span class="text-[10px] text-on-surface-variant">+{{ $dayEvents->count() - 3 }} more</span>
                            @endif
                        </div>
                    </div>
                @endfor

                @php
                    $totalCells = ($startWeekday - 1) + $daysInMonth;
                    $remaining = (7 - ($totalCells % 7)) % 7;
                @endphp
                @for ($blank = 0; $blank < $remaining; $blank++)
                    <div class="min-h-[100px] border-b border-r border-outline-variant/50 bg-surface-container-low/30"></div>
                @endfor
            </div>
        </div>

        <div class="mt-md flex flex-wrap gap-md text-[11px] text-on-surface-variant">
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded bg-yellow-100 border border-yellow-200"></span> {{ __('hfnms.maintenance_status_scheduled') }}</span>
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded bg-red-100 border border-red-200"></span> {{ __('hfnms.overdue') }}</span>
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded bg-blue-100 border border-blue-200"></span> {{ __('hfnms.maintenance_status_in_progress') }}</span>
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded bg-green-100 border border-green-200"></span> {{ __('hfnms.maintenance_status_completed') }}</span>
        </div>
    </div>
</x-app-layout>
