<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.customer_connections') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.customer_connections_subtitle') }}</p>
            </div>
            @can('customer.manage')
                <a href="{{ route('customer-connections.create') }}"
                   class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    <span class="material-symbols-outlined text-[18px]">add_link</span>
                    {{ __('hfnms.add_connection') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        @if (session('success'))
            <div class="mb-md px-md py-sm bg-green-50 border border-green-200 text-success rounded-lg text-body-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.customer') }}</th>
                        <th class="text-left px-lg py-sm">ODP</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.odp_port') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.splitter_port') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.cable_core') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.drop_length') }}</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($connections as $conn)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm">
                                <div class="font-mono text-primary font-semibold">{{ $conn->customer->code }}</div>
                                <div class="text-on-surface-variant">{{ $conn->customer->name }}</div>
                            </td>
                            <td class="px-lg py-sm font-mono">{{ $conn->odp->code ?? '—' }}</td>
                            <td class="px-lg py-sm font-mono">{{ $conn->odp_port_number ?? '—' }}</td>
                            <td class="px-lg py-sm font-mono">{{ $conn->splitterPort?->label ?? '—' }}</td>
                            <td class="px-lg py-sm font-mono text-[12px]">
                                @if ($conn->cableCore)
                                    {{ $conn->cableCore->cable?->code }} #{{ $conn->cableCore->core_number }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-lg py-sm">{{ $conn->drop_length_m ? number_format($conn->drop_length_m, 1).' m' : '—' }}</td>
                            <td class="px-lg py-sm text-right space-x-sm">
                                <a href="{{ route('customer-connections.show', $conn) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view') }}</a>
                                @can('customer.manage')
                                    <a href="{{ route('customer-connections.edit', $conn) }}" class="text-on-surface-variant hover:text-primary">{{ __('hfnms.edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-lg text-center text-on-surface-variant">{{ __('hfnms.no_connections') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-md">{{ $connections->links() }}</div>
    </div>
</x-app-layout>
