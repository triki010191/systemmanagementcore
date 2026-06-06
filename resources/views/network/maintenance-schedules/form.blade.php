@php
    $isEdit = $schedule !== null;
    $action = $isEdit ? route('maintenance-schedules.update', $schedule) : route('maintenance-schedules.store');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">
                {{ $isEdit ? __('hfnms.edit_maintenance') : __('hfnms.add_maintenance') }}
            </h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.maintenance_form_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[900px] mx-auto">
        <form method="POST" action="{{ $action }}" class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.title') }} *</label>
                <input type="text" name="title" value="{{ old('title', $schedule?->title) }}" required
                       class="w-full rounded-lg border-outline-variant text-body-sm">
                @error('title') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.description') }}</label>
                <textarea name="description" rows="3" class="w-full rounded-lg border-outline-variant text-body-sm">{{ old('description', $schedule?->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.schedule_type') }} *</label>
                    <select name="schedule_type" required class="w-full rounded-lg border-outline-variant text-body-sm">
                        @foreach (['preventive', 'corrective', 'inspection'] as $type)
                            <option value="{{ $type }}" @selected(old('schedule_type', $schedule?->schedule_type ?? 'preventive') === $type)>
                                {{ __('hfnms.maintenance_type_'.$type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.priority') }} *</label>
                    <select name="priority" required class="w-full rounded-lg border-outline-variant text-body-sm">
                        @foreach (['low', 'medium', 'high'] as $p)
                            <option value="{{ $p }}" @selected(old('priority', $schedule?->priority ?? 'medium') === $p)>
                                {{ __('hfnms.maintenance_priority_'.$p) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }} *</label>
                    <select name="status" required class="w-full rounded-lg border-outline-variant text-body-sm">
                        @foreach (['scheduled', 'in_progress', 'completed', 'cancelled'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $schedule?->status ?? 'scheduled') === $s)>
                                {{ __('hfnms.maintenance_status_'.$s) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.scheduled_at') }} *</label>
                    <input type="datetime-local" name="scheduled_at" required
                           value="{{ old('scheduled_at', $schedule?->scheduled_at?->format('Y-m-d\TH:i') ?? now()->addDays(7)->format('Y-m-d\TH:i')) }}"
                           class="w-full rounded-lg border-outline-variant text-body-sm">
                    @error('scheduled_at') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.network_node') }}</label>
                    <select name="network_node_id" class="w-full rounded-lg border-outline-variant text-body-sm">
                        <option value="">{{ __('hfnms.none_optional') }}</option>
                        @foreach ($nodes as $node)
                            <option value="{{ $node->id }}" @selected(old('network_node_id', $schedule?->network_node_id) == $node->id)>
                                {{ $node->code }} — {{ $node->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.assignee') }}</label>
                <select name="assigned_to" class="w-full rounded-lg border-outline-variant text-body-sm">
                    <option value="">{{ __('hfnms.unassigned') }}</option>
                    @foreach ($assignees as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_to', $schedule?->assigned_to) == $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save_changes') : __('hfnms.create_maintenance') }}
                </button>
                <a href="{{ $isEdit ? route('maintenance-schedules.show', $schedule) : route('maintenance-schedules.index') }}"
                   class="text-on-surface-variant text-body-sm hover:text-primary">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
