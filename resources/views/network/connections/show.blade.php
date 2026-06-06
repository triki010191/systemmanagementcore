<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ $connection->customer->code }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.connection_detail') }}</p>
            </div>
            <div class="flex items-center gap-sm flex-wrap">
                @if ($pathComplete)
                    <span class="inline-flex items-center gap-xs px-sm py-xs bg-green-50 text-success text-body-sm font-semibold rounded">
                        <span class="w-2 h-2 rounded-full bg-success"></span>
                        {{ __('hfnms.path_complete') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-xs px-sm py-xs bg-red-50 text-error text-body-sm font-semibold rounded">
                        {{ __('hfnms.path_incomplete') }}
                    </span>
                @endif
                <a href="{{ route('path-tracing.index', ['code' => $connection->customer->code]) }}"
                   class="px-md py-sm bg-primary text-on-primary rounded text-body-sm font-semibold hover:opacity-90">
                    {{ __('hfnms.trace_path') }}
                </a>
                @can('customer.manage')
                    <a href="{{ route('customer-connections.edit', $connection) }}"
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
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.customer') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.code') }}</dt><dd class="font-mono font-semibold">{{ $connection->customer->code }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.name') }}</dt><dd>{{ $connection->customer->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">ONU</dt><dd class="font-mono">{{ $connection->customer->onu_serial ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">RX</dt><dd class="font-mono">{{ $connection->customer->rx_power_dbm ?? '—' }} dBm</dd></div>
                </dl>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.termination_points') }}</h3>
                <dl class="space-y-sm text-body-sm">
                    <div class="flex justify-between"><dt class="text-on-surface-variant">ODP</dt><dd class="font-mono">{{ $connection->odp->code }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.odp_port') }}</dt><dd class="font-mono">Port {{ $connection->odp_port_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.splitter_port') }}</dt><dd class="font-mono">{{ $connection->splitterPort?->label ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.cable_core') }}</dt>
                        <dd class="font-mono text-[12px]">
                            @if ($connection->cableCore)
                                {{ $connection->cableCore->cable?->code }} Core {{ $connection->cableCore->core_number }} (Tube {{ $connection->cableCore->tube_number }})
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.drop_length') }}</dt><dd>{{ $connection->drop_length_m ? number_format($connection->drop_length_m, 2).' m' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-on-surface-variant">{{ __('hfnms.connected_at') }}</dt><dd>{{ $connection->connected_at?->format('d M Y') ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="flex items-center gap-md">
            <a href="{{ route('customer-connections.index') }}" class="text-primary text-body-sm font-semibold hover:underline">← {{ __('hfnms.back_to_list') }}</a>
            @can('customer.manage')
                <form method="POST" action="{{ route('customer-connections.destroy', $connection) }}"
                      onsubmit="return confirm('{{ __('hfnms.confirm_delete_connection') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-error text-body-sm font-semibold hover:underline">{{ __('hfnms.delete_connection') }}</button>
                </form>
            @endcan
        </div>
    </div>
</x-app-layout>
