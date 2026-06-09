@php
    $plannedCables = $plannedCables ?? [];
@endphp

@if (! empty($plannedCables))
    <div class="mt-lg bg-amber-50 border border-amber-200 rounded-lg p-lg">
        <div class="flex items-start justify-between gap-md mb-md">
            <div>
                <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.planned_cables_title') }}</h3>
                <p class="text-body-sm text-on-surface-variant mt-xs">{{ __('hfnms.planned_cables_hint') }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-body-sm">
                <thead>
                    <tr class="text-left text-on-surface-variant border-b border-amber-200 text-[11px] uppercase tracking-wide">
                        <th class="py-xs pr-sm">{{ __('hfnms.cable_code') }}</th>
                        <th class="py-xs pr-sm">{{ __('hfnms.route_to') }}</th>
                        <th class="py-xs pr-sm">{{ __('hfnms.core_count') }}</th>
                        <th class="py-xs">{{ __('hfnms.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($plannedCables as $row)
                        <tr class="border-b border-amber-100/80">
                            <td class="py-sm pr-sm font-mono font-semibold">{{ $row['code'] }}</td>
                            <td class="py-sm pr-sm font-mono">{{ $row['other_node_code'] ?? '—' }}</td>
                            <td class="py-sm pr-sm">{{ $row['core_count'] }}</td>
                            <td class="py-sm">
                                @can('cable.manage')
                                    <form method="POST" action="{{ route('cables.activate', $row['id']) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-sm py-xs rounded bg-primary text-on-primary text-[11px] font-semibold hover:opacity-90">
                                            {{ __('hfnms.cable_activate') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[11px] uppercase text-amber-800">planned</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
