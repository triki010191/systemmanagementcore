@php
    use App\Enums\NetworkNodeStatus;

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

        @if ($errors->any() && ! $errors->has('form'))
            <div class="mb-md px-md py-sm bg-red-50 border border-red-200 text-error rounded-lg text-body-sm">
                <p class="font-semibold mb-xs">{{ __('hfnms.form_validation_failed') }}</p>
                <ul class="list-disc pl-lg space-y-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
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
                                {{ $parent->code }} — {{ $parent->name }}@if ($parent->status && $parent->status->value !== 'active') ({{ ucfirst($parent->status->value) }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                <div class="md:col-span-1">
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.gps_coordinates') }}{{ $type->requiresGps() ? ' *' : '' }}</label>
                    @php
                        $gpsValue = old('gps_coordinates');
                        if (! $gpsValue && $node?->latitude !== null && $node?->longitude !== null) {
                            $gpsValue = \App\Support\GpsCoordinateParser::format((float) $node->latitude, (float) $node->longitude);
                        }
                    @endphp
                    <input type="text" name="gps_coordinates" value="{{ $gpsValue }}"
                           {{ $type->requiresGps() ? 'required' : '' }}
                           placeholder="-6.312098311370568, 105.99951105581076"
                           class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary font-mono text-body-sm">
                    <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.gps_coordinates_help') }}</p>
                    @error('gps_coordinates') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    @error('latitude') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    @error('longitude') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }} *</label>
                    <select name="status" required class="w-full rounded-lg border-outline-variant focus:border-primary focus:ring-primary text-body-sm">
                        @foreach (NetworkNodeStatus::cases() as $nodeStatus)
                            <option value="{{ $nodeStatus->value }}" @selected(old('status', $node?->status?->value ?? NetworkNodeStatus::Active->value) === $nodeStatus->value)>
                                {{ ucfirst($nodeStatus->value) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
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
                <a href="{{ $isEdit ? route('network.assets.show', [$type->routeSlug(), $asset->id]) : route('network.assets.index', $type->routeSlug()) }}" class="text-on-surface-variant hover:text-primary text-body-sm">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>

        @if ($isEdit && in_array($type, [\App\Enums\NetworkNodeType::Odc, \App\Enums\NetworkNodeType::Odp], true) && ! empty($plannedCables))
            @include('network.assets.partials.planned-cables', ['plannedCables' => $plannedCables])
        @endif

        @if ($isEdit && $type === \App\Enums\NetworkNodeType::Odc && ! empty($odcDistribution))
            @include('network.assets.partials.odc-distribution', [
                'asset' => $asset,
                'odcDistribution' => $odcDistribution,
            ])
        @endif

        @if ($isEdit)
            @can('network.edit')
                <form method="POST" action="{{ route('network.assets.destroy', [$type->routeSlug(), $asset->id]) }}" class="mt-md"
                      onsubmit="return confirm(@js(__('hfnms.confirm_delete')))">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-error text-body-sm font-semibold hover:underline">{{ __('hfnms.delete_asset') }}</button>
                </form>
            @endcan
        @endif
    </div>
</x-app-layout>
