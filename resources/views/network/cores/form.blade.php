
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.edit_core') }}</h2>
                <p class="text-body-sm text-on-surface-variant">
                    {{ $core->cable?->code }} · Tube {{ $core->tube_number }} · Core {{ $core->core_number }}
                </p>
            </div>
            <a href="{{ route('cores.index', ['cable_id' => $core->cable_id]) }}" class="text-primary text-body-sm font-semibold hover:underline">
                ← {{ __('hfnms.back') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-[720px] mx-auto">
        @if ($errors->has('form'))
            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $errors->first('form') }}</div>
        @endif

        <div class="mb-md bg-surface-container-low border border-outline-variant rounded-lg p-md text-body-sm">
            <dl class="grid grid-cols-2 gap-sm">
                <div><dt class="text-on-surface-variant">{{ __('hfnms.cable') }}</dt><dd class="font-mono font-semibold">{{ $core->cable?->code }}</dd></div>
                <div><dt class="text-on-surface-variant">Tube / Core</dt><dd class="font-mono">#{{ $core->tube_number }} / #{{ $core->core_number }}</dd></div>
                <div><dt class="text-on-surface-variant">{{ __('hfnms.color') }}</dt><dd>{{ $core->color_name ?? $core->tubeColor?->color_name ?? '—' }}</dd></div>
                <div><dt class="text-on-surface-variant">{{ __('hfnms.position') }}</dt><dd class="font-mono">{{ $core->core_position_in_tube ?? '—' }}</dd></div>
            </dl>
        </div>

        <form method="POST" action="{{ route('cores.update', $core) }}" class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }} *</label>
                    <select name="status" required class="w-full rounded-lg border-outline-variant text-body-sm">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $core->status->value) === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.total_loss') }} (dB)</label>
                    <input type="number" step="0.01" min="0" name="loss_db"
                           value="{{ old('loss_db', $core->loss_db) }}"
                           class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                    @error('loss_db') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.source_node') }}</label>
                    <select name="source_node_id" class="w-full rounded-lg border-outline-variant text-body-sm">
                        <option value="">—</option>
                        @foreach ($nodes as $nodeOption)
                            <option value="{{ $nodeOption->id }}" @selected(old('source_node_id', $core->source_node_id) == $nodeOption->id)>
                                [{{ strtoupper($nodeOption->type->value) }}] {{ $nodeOption->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('source_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.target_node') }}</label>
                    <select name="target_node_id" class="w-full rounded-lg border-outline-variant text-body-sm">
                        <option value="">—</option>
                        @foreach ($nodes as $nodeOption)
                            <option value="{{ $nodeOption->id }}" @selected(old('target_node_id', $core->target_node_id) == $nodeOption->id)>
                                [{{ strtoupper($nodeOption->type->value) }}] {{ $nodeOption->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('target_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-between gap-md pt-md border-t border-outline-variant">
                <div class="flex items-center gap-md">
                    <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                        {{ __('hfnms.save_changes') }}
                    </button>
                    <a href="{{ route('cores.index', ['cable_id' => $core->cable_id]) }}" class="text-on-surface-variant hover:text-primary text-body-sm">{{ __('hfnms.cancel') }}</a>
                </div>
            </div>
        </form>

        <div class="flex items-center justify-between gap-md mt-md">
            @if ($canDelete)
                <form method="POST" action="{{ route('cores.destroy', $core) }}"
                      onsubmit="return confirm(@js(__('hfnms.confirm_delete_core')))">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-error text-body-sm font-semibold hover:underline">{{ __('hfnms.delete_core') }}</button>
                </form>
            @else
                <p class="text-[11px] text-outline">{{ __('hfnms.core_delete_blocked_hint') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
