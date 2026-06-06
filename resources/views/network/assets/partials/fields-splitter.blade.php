<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Ratio *</label>
        <select name="ratio" required class="w-full rounded-lg border-outline-variant text-body-sm">
            @foreach ($splitterRatios as $ratio)
                <option value="{{ $ratio->value }}" @selected(old('ratio', $asset?->ratio?->value ?? '1:8') === $ratio->value)>{{ $ratio->value }}</option>
            @endforeach
        </select>
        @error('ratio') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Brand</label>
        <input type="text" name="brand" value="{{ old('brand', $asset?->brand) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
</div>
