<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ $schedule->title }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.maintenance_detail') }}</p>
            </div>
            @can('maintenance.manage')
                <a href="{{ route('maintenance-schedules.edit', $schedule) }}"
                   class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                    {{ __('hfnms.edit') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1000px] mx-auto space-y-lg">
        @if (session('success'))
            <div class="px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.schedule_info') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.schedule_type') }}</dt>
                        <dd>{{ __('hfnms.maintenance_type_'.$schedule->schedule_type) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.priority') }}</dt>
                        <dd>{{ __('hfnms.maintenance_priority_'.$schedule->priority) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.status') }}</dt>
                        <dd>{{ __('hfnms.maintenance_status_'.$schedule->status) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.scheduled_at') }}</dt>
                        <dd>{{ $schedule->scheduled_at->format('d M Y H:i') }}</dd></div>
                    @if ($schedule->completed_at)
                        <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.completed_at') }}</dt>
                            <dd>{{ $schedule->completed_at->format('d M Y H:i') }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.assignee') }}</dt>
                        <dd>{{ $schedule->assignee?->name ?? __('hfnms.unassigned') }}</dd></div>
                </dl>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.network_node') }}</h3>
                @if ($schedule->networkNode)
                    <dl class="space-y-sm text-body-sm">
                        <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.code') }}</dt>
                            <dd class="font-mono">{{ $schedule->networkNode->code }}</dd></div>
                        <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.name') }}</dt>
                            <dd>{{ $schedule->networkNode->name }}</dd></div>
                    </dl>
                @else
                    <p class="text-on-surface-variant text-body-sm">{{ __('hfnms.no_related_assets') }}</p>
                @endif
            </div>
        </div>

        @if ($schedule->description)
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.description') }}</h3>
                <p class="text-body-sm whitespace-pre-wrap">{{ $schedule->description }}</p>
            </div>
        @endif

        <div class="flex items-center gap-md">
            <a href="{{ route('maintenance-schedules.index') }}" class="text-primary text-body-sm font-semibold hover:underline">← {{ __('hfnms.back_to_list') }}</a>
            @can('maintenance.manage')
                <form method="POST" action="{{ route('maintenance-schedules.destroy', $schedule) }}"
                      onsubmit="return confirm('{{ __('hfnms.confirm_delete_maintenance') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-error text-body-sm font-semibold hover:underline">{{ __('hfnms.delete_maintenance') }}</button>
                </form>
            @endcan
        </div>
    </div>
</x-app-layout>
