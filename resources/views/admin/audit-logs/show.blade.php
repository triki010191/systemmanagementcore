<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.audit_log_detail') }}</h2>
            <p class="text-body-sm text-on-surface-variant font-mono">{{ $log->entity_type }} #{{ $log->entity_id ?? '—' }} · {{ $log->action }}</p>
        </div>
    </x-slot>

    <div class="max-w-[1000px] mx-auto space-y-lg">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-sm text-body-sm">
                <div class="flex justify-between md:block"><dt class="text-on-surface-variant">{{ __('hfnms.timestamp') }}</dt><dd>{{ $log->created_at->format('d M Y H:i:s') }}</dd></div>
                <div class="flex justify-between md:block"><dt class="text-on-surface-variant">{{ __('hfnms.user') }}</dt><dd>{{ $log->user?->name ?? '—' }}</dd></div>
                <div class="flex justify-between md:block"><dt class="text-on-surface-variant">{{ __('hfnms.action') }}</dt><dd class="font-mono uppercase">{{ $log->action }}</dd></div>
                <div class="flex justify-between md:block"><dt class="text-on-surface-variant">{{ __('hfnms.entity_type') }}</dt><dd class="font-mono">{{ $log->entity_type }}</dd></div>
                <div class="flex justify-between md:block"><dt class="text-on-surface-variant">Entity ID</dt><dd class="font-mono">{{ $log->entity_id ?? '—' }}</dd></div>
                <div class="flex justify-between md:block"><dt class="text-on-surface-variant">IP</dt><dd class="font-mono">{{ $log->ip_address ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.old_values') }}</h3>
                @if ($log->old_values)
                    <pre class="text-[11px] font-mono bg-surface-container-low p-md rounded overflow-x-auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <p class="text-on-surface-variant text-body-sm">—</p>
                @endif
            </div>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
                <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.new_values') }}</h3>
                @if ($log->new_values)
                    <pre class="text-[11px] font-mono bg-surface-container-low p-md rounded overflow-x-auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <p class="text-on-surface-variant text-body-sm">—</p>
                @endif
            </div>
        </div>

        <a href="{{ route('audit-logs.index') }}" class="text-primary text-body-sm font-semibold hover:underline">← {{ __('hfnms.back_to_list') }}</a>
    </div>
</x-app-layout>
