<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.trouble_tickets') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.trouble_tickets_subtitle') }}</p>
            </div>
            @can('fault.manage')
                <a href="{{ route('trouble-tickets.create') }}"
                   class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    {{ __('hfnms.add_ticket') }}
                </a>
            @endcan
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
                @foreach (['open', 'in_progress', 'resolved', 'closed'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ __('hfnms.ticket_status_'.$s) }}</option>
                @endforeach
            </select>
        </form>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.ticket') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.title') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.priority') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.status') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.node_or_customer') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.assignee') }}</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm font-mono text-primary font-semibold">{{ $ticket->ticket_number }}</td>
                            <td class="px-lg py-sm">{{ Str::limit($ticket->title, 50) }}</td>
                            <td class="px-lg py-sm">
                                @php
                                    $priorityClass = match ($ticket->priority) {
                                        'critical' => 'bg-red-100 text-error',
                                        'high' => 'bg-orange-100 text-orange-700',
                                        'medium' => 'bg-yellow-100 text-yellow-700',
                                        default => 'bg-surface-container-high text-on-surface-variant',
                                    };
                                @endphp
                                <span class="text-[11px] px-sm py-xs rounded font-bold {{ $priorityClass }}">{{ __('hfnms.ticket_priority_'.$ticket->priority) }}</span>
                            </td>
                            <td class="px-lg py-sm">{{ __('hfnms.ticket_status_'.$ticket->status) }}</td>
                            <td class="px-lg py-sm text-[12px]">
                                @if ($ticket->customer)
                                    <span class="font-mono">{{ $ticket->customer->code }}</span>
                                @elseif ($ticket->networkNode)
                                    <span class="font-mono">{{ $ticket->networkNode->code }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-lg py-sm">{{ $ticket->assignee?->name ?? '—' }}</td>
                            <td class="px-lg py-sm text-right space-x-sm">
                                <a href="{{ route('trouble-tickets.show', $ticket) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view') }}</a>
                                @can('fault.manage')
                                    <a href="{{ route('trouble-tickets.edit', $ticket) }}" class="text-on-surface-variant hover:text-primary">{{ __('hfnms.edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_tickets') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="mt-md">{{ $tickets->links() }}</div>
        @endif
    </div>
</x-app-layout>
