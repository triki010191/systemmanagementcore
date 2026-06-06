<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.audit_logs') }}</h2>
            <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.audit_logs_subtitle') }}</p>
        </div>
    </x-slot>

    <div class="max-w-[1600px] mx-auto">
        <form method="GET" class="mb-md flex flex-wrap gap-sm items-end bg-surface-container-lowest border border-outline-variant rounded-lg p-md">
            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.entity_type') }}</label>
                <select name="entity_type" class="rounded-lg border-outline-variant text-body-sm">
                    <option value="">{{ __('hfnms.all') }}</option>
                    @foreach ($entityTypes as $type)
                        <option value="{{ $type }}" @selected($filters['entity_type'] === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.action') }}</label>
                <select name="action" class="rounded-lg border-outline-variant text-body-sm">
                    <option value="">{{ __('hfnms.all') }}</option>
                    @foreach ($actions as $act)
                        <option value="{{ $act }}" @selected($filters['action'] === $act)>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.user') }}</label>
                <select name="user_id" class="rounded-lg border-outline-variant text-body-sm">
                    <option value="">{{ __('hfnms.all') }}</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $filters['user_id'] === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">{{ __('hfnms.filter') }}</button>
            <a href="{{ route('audit-logs.export', request()->only(['entity_type', 'action', 'user_id'])) }}"
               class="px-md py-sm border border-outline-variant rounded font-semibold text-body-sm hover:bg-surface-container-low inline-flex items-center gap-xs">
                <span class="material-symbols-outlined text-[16px]">download</span>
                {{ __('hfnms.export_csv') }}
            </a>
        </form>

        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg overflow-hidden">
            <table class="w-full text-body-sm">
                <thead class="bg-surface-container-low text-on-surface-variant text-label-caps">
                    <tr>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.timestamp') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.user') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.action') }}</th>
                        <th class="text-left px-lg py-sm">{{ __('hfnms.entity_type') }}</th>
                        <th class="text-left px-lg py-sm">ID</th>
                        <th class="text-left px-lg py-sm">IP</th>
                        <th class="text-right px-lg py-sm"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-t border-outline-variant hover:bg-surface-container-low/50">
                            <td class="px-lg py-sm whitespace-nowrap">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-lg py-sm">{{ $log->user?->name ?? '—' }}</td>
                            <td class="px-lg py-sm">
                                <span class="text-[11px] px-sm py-xs rounded font-bold uppercase bg-surface-container-high">{{ $log->action }}</span>
                            </td>
                            <td class="px-lg py-sm font-mono text-[12px]">{{ $log->entity_type }}</td>
                            <td class="px-lg py-sm font-mono">{{ $log->entity_id ?? '—' }}</td>
                            <td class="px-lg py-sm font-mono text-[11px]">{{ $log->ip_address ?? '—' }}</td>
                            <td class="px-lg py-sm text-right">
                                <a href="{{ route('audit-logs.show', $log) }}" class="text-primary font-semibold hover:underline">{{ __('hfnms.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-lg py-xl text-center text-on-surface-variant">{{ __('hfnms.no_audit_logs') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="mt-md">{{ $logs->links() }}</div>
        @endif
    </div>
</x-app-layout>
