@if ($type->requiresQr())
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg mb-lg">
        <h3 class="text-label-caps text-on-surface-variant mb-md">{{ __('hfnms.qr_code') }}</h3>

        <div class="flex flex-col sm:flex-row gap-lg items-start">
            @if ($qrUrl)
                <img src="{{ $qrUrl }}" alt="QR" class="w-40 h-40 border border-outline-variant rounded-lg shrink-0">
            @else
                <div class="w-40 h-40 border border-dashed border-outline-variant rounded-lg flex items-center justify-center text-on-surface-variant text-body-sm text-center p-sm">
                    {{ __('hfnms.qr_not_generated') }}
                </div>
            @endif
            <div class="flex-1 space-y-sm text-body-sm">
                <p class="text-on-surface-variant">{{ __('hfnms.scan_url') }}:</p>
                <p class="font-mono text-[12px] break-all bg-surface-container-low p-sm rounded">{{ $scanUrl }}</p>
                @can('network.edit')
                    <form method="POST" action="{{ route('network.assets.qr.generate', [$type->routeSlug(), $asset->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded text-body-sm font-semibold hover:opacity-90">
                            <span class="material-symbols-outlined text-[18px]">qr_code_2</span>
                            {{ $qrUrl ? __('hfnms.regenerate_qr') : __('hfnms.generate_qr') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
@endif

<div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
    <div class="flex justify-between items-start mb-md">
        <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.asset_photos') }}</h3>
        @if ($photoCompliance['is_compliant'] ?? false)
            <span class="text-[11px] px-sm py-xs bg-green-50 text-success rounded font-bold">{{ __('hfnms.photo_compliant') }}</span>
        @else
            <span class="text-[11px] px-sm py-xs bg-yellow-50 text-yellow-700 rounded font-bold">{{ __('hfnms.photo_incomplete') }}</span>
        @endif
    </div>

    @can('network.edit')
        <form method="POST" action="{{ route('network.assets.photos.store', [$type->routeSlug(), $asset->id]) }}"
              enctype="multipart/form-data" class="mb-lg p-md bg-surface-container-low rounded-lg space-y-md">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.photo_type') }}</label>
                    <select name="photo_type" required class="w-full rounded-lg border-outline-variant text-body-sm">
                        @foreach (['location', 'device', 'label', 'splice', 'other'] as $pt)
                            <option value="{{ $pt }}">{{ ucfirst($pt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.photo_file') }}</label>
                    <input type="file" name="photo" accept="image/*" required class="w-full text-body-sm">
                </div>
                <div>
                    <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.caption') }}</label>
                    <input type="text" name="caption" class="w-full rounded-lg border-outline-variant text-body-sm" placeholder="{{ __('hfnms.optional') }}">
                </div>
            </div>
            <button type="submit" class="px-md py-sm bg-primary text-on-primary rounded text-body-sm font-semibold hover:opacity-90">
                {{ __('hfnms.upload_photo') }}
            </button>
        </form>
    @endcan

    @if ($photos->isEmpty())
        <p class="text-on-surface-variant text-body-sm">{{ __('hfnms.no_photos') }}</p>
        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.photo_requirement_hint') }}</p>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 gap-md">
            @foreach ($photos as $photo)
                <div class="border border-outline-variant rounded-lg overflow-hidden group relative">
                    <img src="{{ Storage::disk('public')->url($photo->file_path) }}" alt="{{ $photo->caption }}" class="w-full aspect-video object-cover">
                    <div class="p-sm text-body-sm">
                        <span class="text-label-caps text-on-surface-variant uppercase">{{ $photo->photo_type }}</span>
                        @if ($photo->caption)<p class="text-[12px] mt-xs">{{ $photo->caption }}</p>@endif
                    </div>
                    @can('network.edit')
                        <form method="POST" action="{{ route('asset-photos.destroy', $photo) }}" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                              onsubmit="return confirm('{{ __('hfnms.confirm_delete_photo') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="bg-error text-white rounded-full w-8 h-8 flex items-center justify-center shadow">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </form>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif
</div>
