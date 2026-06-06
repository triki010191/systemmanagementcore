@php
    $node = $asset?->networkNode;
    $isEdit = $asset !== null;
    $action = $isEdit
        ? route('network.assets.update', [$type->routeSlug(), $asset->id])
        : route('network.assets.store', $type->routeSlug());
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-headline-md text-on-surface">
                {{ $isEdit ? __('hfnms.edit_asset') : __('hfnms.add_asset', ['type' => $type->label()]) }}
            </h2>
            <p class="text-body-sm text-on-surface-variant">{{ $type->label() }}</p>
        </div>
    </x-slot>

    <div class="max-w-[900px] mx-auto">
        <x-network.asset-tabs :active="$type" />

        @if ($errors->has('form'))
            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">{{ $errors->first('form') }}</div>
        @endif

        <form method="POST" action="{{ $action }}" class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.name') }} *</label>
                    <input type="text" name="name" value="{{ old('name', $node?->name) }}" required
                           class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary text-body-sm">
                    @error('name') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.code') }}{{ $type->usesAutoCode() ? '' : ' *' }}</label>
                    <input type="text" name="code" value="{{ old('code', $node?->code ?? $suggestedCode) }}"
                           placeholder="{{ $suggestedCode }}"
                           class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary font-mono text-body-sm">
                    @if ($suggestedCode)
                        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.suggested_code') }}: {{ $suggestedCode }}</p>
                    @endif
                    @error('code') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            </div>

            @if ($type->expectedParentType())
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.parent') }} ({{ $type->expectedParentType()->label() }}) *</label>
                    <select name="parent_id" required class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary text-body-sm">
                        <option value="">{{ __('hfnms.select_parent') }}</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id', $node?->parent_id) == $parent->id)>
                                {{ $parent->code }} — {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">Latitude{{ $type->requiresGps() ? ' *' : '' }}</label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $node?->latitude) }}"
                           {{ $type->requiresGps() ? 'required' : '' }}
                           class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary font-mono text-body-sm">
                    @error('latitude') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">Longitude{{ $type->requiresGps() ? ' *' : '' }}</label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $node?->longitude) }}"
                           {{ $type->requiresGps() ? 'required' : '' }}
                           class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary font-mono text-body-sm">
                    @error('longitude') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }} *</label>
                    <select name="status" class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary text-body-sm">
                        @foreach (['active', 'inactive', 'maintenance', 'fault'] as $st)
                            <option value="{{ $st }}" @selected(old('status', $node?->status?->value ?? 'active') === $st)>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.address') }}</label>
                <textarea name="address" rows="2" class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary text-body-sm">{{ old('address', $node?->address) }}</textarea>
            </div>

            @include('network.assets.partials.fields-' . $type->value, ['asset' => $asset])

            <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save_changes') : __('hfnms.create_asset') }}
                </button>
                <a href="{{ route('network.assets.index', $type->routeSlug()) }}" class="text-on-surface-variant hover:text-primary text-body-sm">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
