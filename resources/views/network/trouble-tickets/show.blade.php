<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ $ticket->ticket_number }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ $ticket->title }}</p>
            </div>
            <div class="flex items-center gap-sm flex-wrap">
                @can('fault.manage')
                    <a href="{{ route('trouble-tickets.edit', $ticket) }}"
                       class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                        {{ __('hfnms.edit') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="max-w-[1000px] mx-auto space-y-lg">
        @if (session('success'))
            <div class="px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.ticket_details') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.priority') }}</dt>
                        <dd>{{ __('hfnms.ticket_priority_'.$ticket->priority) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.status') }}</dt>
                        <dd>{{ __('hfnms.ticket_status_'.$ticket->status) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.assignee') }}</dt>
                        <dd>{{ $ticket->assignee?->name ?? __('hfnms.unassigned') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.created_at') }}</dt>
                        <dd>{{ $ticket->created_at->format('d M Y H:i') }}</dd></div>
                    @if ($ticket->resolved_at)
                        <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.resolved_at') }}</dt>
                            <dd>{{ $ticket->resolved_at->format('d M Y H:i') }}</dd></div>
                    @endif
                </dl>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.related_assets') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    @if ($ticket->networkNode)
                        <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.network_node') }}</dt>
                            <dd class="font-mono">{{ $ticket->networkNode->code }}</dd></div>
                    @endif
                    @if ($ticket->customer)
                        <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.customer') }}</dt>
                            <dd class="font-mono">{{ $ticket->customer->code }}</dd></div>
                        <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.name') }}</dt>
                            <dd>{{ $ticket->customer->name }}</dd></div>
                    @endif
                    @if (! $ticket->networkNode && ! $ticket->customer)
                        <p class="text-on-surface-variant">{{ __('hfnms.no_related_assets') }}</p>
                    @endif
                </dl>
            </div>
        </div>

        @if ($ticket->description)
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.description') }}</h3>
                <p class="text-body-sm whitespace-pre-wrap">{{ $ticket->description }}</p>
            </div>
        @endif

        <div class="flex items-center gap-md">
            <a href="{{ route('trouble-tickets.index') }}" class="text-primary text-body-sm font-semibold hover:underline">← {{ __('hfnms.back_to_list') }}</a>
            @can('fault.manage')
                <form method="POST" action="{{ route('trouble-tickets.destroy', $ticket) }}"
                      onsubmit="return confirm('{{ __('hfnms.confirm_delete_ticket') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-error text-body-sm font-semibold hover:underline">{{ __('hfnms.delete_ticket') }}</button>
                </form>
            @endcan
        </div>
    </div>
</x-app-layout>
