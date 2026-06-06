@php
    $isEdit = $ticket !== null;
    $action = $isEdit ? route('trouble-tickets.update', $ticket) : route('trouble-tickets.store');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">
                {{ $isEdit ? __('hfnms.edit_ticket') : __('hfnms.add_ticket') }}
            </h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.ticket_form_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[900px] mx-auto">
        <form method="POST" action="{{ $action }}" class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.title') }} *</label>
                <input type="text" name="title" value="{{ old('title', $ticket?->title) }}" required
                       class="w-full rounded-lg border-outline-variant text-body-sm">
                @error('title') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.description') }}</label>
                <textarea name="description" rows="4" class="w-full rounded-lg border-outline-variant text-body-sm">{{ old('description', $ticket?->description) }}</textarea>
                @error('description') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.priority') }} *</label>
                    <select name="priority" required class="w-full rounded-lg border-outline-variant text-body-sm">
                        @foreach (['low', 'medium', 'high', 'critical'] as $p)
                            <option value="{{ $p }}" @selected(old('priority', $ticket?->priority ?? 'medium') === $p)>{{ __('hfnms.ticket_priority_'.$p) }}</option>
                        @endforeach
                    </select>
                    @error('priority') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }} *</label>
                    <select name="status" required class="w-full rounded-lg border-outline-variant text-body-sm">
                        @foreach (['open', 'in_progress', 'resolved', 'closed'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $ticket?->status ?? 'open') === $s)>{{ __('hfnms.ticket_status_'.$s) }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.network_node') }}</label>
                    <select name="network_node_id" class="w-full rounded-lg border-outline-variant text-body-sm">
                        <option value="">{{ __('hfnms.none_optional') }}</option>
                        @foreach ($nodes as $node)
                            <option value="{{ $node->id }}" @selected(old('network_node_id', $ticket?->network_node_id) == $node->id)>
                                {{ $node->code }} — {{ $node->name }} ({{ $node->type->label() }})
                            </option>
                        @endforeach
                    </select>
                    @error('network_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.customer') }}</label>
                    <select name="customer_id" class="w-full rounded-lg border-outline-variant text-body-sm">
                        <option value="">{{ __('hfnms.none_optional') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id', $ticket?->customer_id) == $customer->id)>
                                {{ $customer->code }} — {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.assignee') }}</label>
                <select name="assigned_to" class="w-full rounded-lg border-outline-variant text-body-sm">
                    <option value="">{{ __('hfnms.unassigned') }}</option>
                    @foreach ($assignees as $user)
                        <option value="{{ $user->id }}" @selected(old('assigned_to', $ticket?->assigned_to) == $user->id)>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
                @error('assigned_to') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save_changes') : __('hfnms.create_ticket') }}
                </button>
                <a href="{{ $isEdit ? route('trouble-tickets.show', $ticket) : route('trouble-tickets.index') }}"
                   class="text-on-surface-variant text-body-sm hover:text-primary">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
