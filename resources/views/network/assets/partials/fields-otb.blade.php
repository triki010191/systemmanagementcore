<div class="grid grid-cols-1 md:grid-cols-2 gap-md">
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Tray Count</label>
        <input type="number" name="tray_count" value="{{ old('tray_count', $asset?->tray_count) }}" class="w-full rounded-lg border-outline-variant text-body-sm">
    </div>
    <div>
        <label class="text-label-caps text-on-surface-variant block mb-xs">Capacity Cores</label>
        <select name="capacity_cores" class="w-full rounded-lg border-outline-variant text-body-sm">
            <option value="">—</option>
            @foreach ([12, 24, 48, 96] as $cores)
                <option value="{{ $cores }}" @selected(old('capacity_cores', $asset?->capacity_cores) == $cores)>{{ $cores }} core</option>
            @endforeach
        </select>
    </div>
</div>
