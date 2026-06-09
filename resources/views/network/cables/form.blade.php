@php
    $isEdit = $cable !== null;
    $action = $isEdit ? route('cables.update', $cable) : route('cables.store');
    $routeText = old('route_geometry_text');

    if ($routeText === null && $cable?->route_geometry) {
        $routeText = collect($cable->route_geometry)
            ->map(fn ($point) => \App\Support\GpsCoordinateParser::format(
                (float) ($point['lat'] ?? $point[0] ?? 0),
                (float) ($point['lng'] ?? $point[1] ?? 0)
            ))
            ->implode("\n");
    }

    $routeText = $routeText ?? '';
    $mapNodes = $nodes
        ->filter(fn ($node) => is_numeric($node->latitude) && is_numeric($node->longitude))
        ->map(fn ($node) => [
            'id' => $node->id,
            'code' => $node->code,
            'name' => $node->name,
            'type' => $node->type->value,
            'lat' => (float) $node->latitude,
            'lng' => (float) $node->longitude,
        ])
        ->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">
                    {{ $isEdit ? __('hfnms.edit_cable') : __('hfnms.add_cable') }}
                </h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.cable_form_subtitle') }}</p>
            </div>
            @if ($isEdit)
                <a href="{{ $gisMapUrl }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-xs px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                    <span class="material-symbols-outlined text-[18px]">map</span>
                    {{ __('hfnms.cable_view_gis') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-[1100px] mx-auto">
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

        <form method="POST" action="{{ $action }}" class="space-y-lg"
              x-data="cableRouteMapForm({
                routeUrl: @js(route('gis.route')),
                nodes: @js($mapNodes),
                initialRouteText: @js($routeText),
                initialStartId: @js(old('start_node_id', $cable?->start_node_id)),
                initialEndId: @js(old('end_node_id', $cable?->end_node_id)),
                initialCableType: @js(old('cable_type', $cable?->cable_type ?? 'distribution')),
              })">
            @csrf
            @if ($isEdit) @method('PUT') @endif

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_code') }} *</label>
                        <input type="text" name="code" value="{{ old('code', $cable?->code) }}" required
                               class="w-full rounded-lg border-outline-variant font-mono text-body-sm">
                        @error('code') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_name') }} *</label>
                        <input type="text" name="name" value="{{ old('name', $cable?->name) }}" required
                               class="w-full rounded-lg border-outline-variant text-body-sm">
                        @error('name') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_type') }} *</label>
                        <select name="cable_type" required class="w-full rounded-lg border-outline-variant text-body-sm"
                                x-model="cableType" @change="refreshRouteStyle()">
                            @foreach (['backbone', 'distribution', 'drop'] as $type)
                                <option value="{{ $type }}" @selected(old('cable_type', $cable?->cable_type ?? 'distribution') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                        @error('cable_type') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.core_count') }} *</label>
                        <select name="core_count" required class="w-full rounded-lg border-outline-variant text-body-sm">
                            @foreach ([12, 24, 48, 96] as $count)
                                <option value="{{ $count }}" @selected(old('core_count', $cable?->core_count ?? 12) == $count)>{{ $count }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.tube_count_auto_hint') }}</p>
                        @error('core_count') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.status') }} *</label>
                        <select name="status" required class="w-full rounded-lg border-outline-variant text-body-sm">
                            @foreach (['planned', 'active', 'inactive', 'damaged'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $cable?->status ?? 'planned') === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.cable_planned_hint') }}</p>
                        @error('status') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_start_node') }}</label>
                        <select name="start_node_id" class="w-full rounded-lg border-outline-variant text-body-sm"
                                x-model="startNodeId" @change="onEndpointChange()">
                            <option value="">{{ __('hfnms.select_node') }}</option>
                            @foreach ($nodes as $node)
                                <option value="{{ $node->id }}" @selected(old('start_node_id', $cable?->start_node_id) == $node->id)>
                                    [{{ strtoupper($node->type->value) }}] {{ $node->code }} — {{ $node->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.cable_start_node_help') }}</p>
                        @error('start_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.cable_end_node_optional') }}</label>
                        <select name="end_node_id" class="w-full rounded-lg border-outline-variant text-body-sm"
                                x-model="endNodeId" @change="onEndpointChange()">
                            <option value="">{{ __('hfnms.cable_end_node_empty') }}</option>
                            @foreach ($nodes as $node)
                                <option value="{{ $node->id }}" @selected(old('end_node_id', $cable?->end_node_id) == $node->id)>
                                    [{{ strtoupper($node->type->value) }}] {{ $node->code }} — {{ $node->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.cable_end_node_help') }}</p>
                        @error('end_node_id') <p class="text-error text-body-sm mt-xs">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.length') }} (m)</label>
                        <input type="number" step="0.01" name="length_meters" value="{{ old('length_meters', $cable?->length_meters) }}"
                               class="w-full rounded-lg border-outline-variant text-body-sm">
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.manufacturer') }}</label>
                        <input type="text" name="manufacturer" value="{{ old('manufacturer', $cable?->manufacturer) }}"
                               class="w-full rounded-lg border-outline-variant text-body-sm">
                    </div>
                    <div>
                        <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.fiber_type') }}</label>
                        <input type="text" name="fiber_type" value="{{ old('fiber_type', $cable?->fiber_type) }}"
                               placeholder="G.652.D" class="w-full rounded-lg border-outline-variant text-body-sm">
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg space-y-md">
                <div>
                    <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.cable_route_map_title') }}</h3>
                    <p class="text-body-sm text-on-surface-variant mt-xs">{{ __('hfnms.cable_route_map_hint') }}</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
                    <div class="space-y-md">
                        <div>
                            <label class="text-label-caps text-on-surface-variant block mb-xs">{{ __('hfnms.route_geometry') }}</label>
                            <textarea name="route_geometry_text" rows="8" x-ref="routeText" x-model="routeText"
                                      @input.debounce.400ms="parseRouteText()"
                                      placeholder="-6.1755, 106.8655&#10;-6.1760, 106.8660"
                                      class="w-full rounded-lg border-outline-variant font-mono text-body-sm"></textarea>
                            <p class="text-[11px] text-outline mt-xs">{{ __('hfnms.route_geometry_help') }}</p>
                        </div>

                        <label class="flex items-start gap-sm text-body-sm cursor-pointer">
                            <input type="hidden" name="snap_to_roads" value="0">
                            <input type="checkbox" name="snap_to_roads" value="1" x-model="snapToRoads"
                                   @change="onSnapToggle()"
                                   class="mt-[2px] rounded border-outline-variant text-primary focus:ring-primary">
                            <span>{{ __('hfnms.snap_to_roads') }}<br><span class="text-[11px] text-outline">{{ __('hfnms.snap_to_roads_help') }}</span></span>
                        </label>

                        <div class="flex flex-wrap gap-sm">
                            <button type="button" @click="loadFromEndpoints()"
                                    class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                                {{ __('hfnms.cable_map_load_endpoints') }}
                            </button>
                            <button type="button" @click="snapRouteToRoads()"
                                    class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                                {{ __('hfnms.cable_map_snap_route') }}
                            </button>
                            <button type="button" @click="undoLastPoint()"
                                    class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold hover:bg-surface-container-low">
                                {{ __('hfnms.cable_map_undo_point') }}
                            </button>
                            <button type="button" @click="clearRoute()"
                                    class="px-md py-sm border border-outline-variant rounded text-body-sm font-semibold text-error hover:bg-red-50">
                                {{ __('hfnms.cable_map_clear_route') }}
                            </button>
                        </div>

                        <p class="text-[11px] text-on-surface-variant" x-show="waypoints.length > 0">
                            <span x-text="waypoints.length"></span> {{ __('hfnms.cable_map_point_count') }}
                        </p>
                    </div>

                    <div>
                        <div id="cable-route-map" class="h-[380px] rounded-lg border border-outline-variant overflow-hidden bg-surface-container-low"></div>
                        <div class="mt-sm flex flex-wrap gap-sm text-[11px] text-on-surface-variant">
                            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#2E7D32]"></span> {{ __('hfnms.cable_map_start') }}</span>
                            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#C62828]"></span> {{ __('hfnms.cable_map_end') }}</span>
                            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#1565C0]"></span> {{ __('hfnms.cable_map_waypoint') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-md">
                <button type="submit" class="px-lg py-sm bg-primary text-on-primary rounded font-semibold text-body-sm hover:opacity-90">
                    {{ $isEdit ? __('hfnms.save_changes') : __('hfnms.create_cable') }}
                </button>
                <a href="{{ $isEdit ? route('cables.show', $cable) : route('cables.index') }}" class="text-on-surface-variant hover:text-primary text-body-sm">{{ __('hfnms.cancel') }}</a>
            </div>
        </form>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        function cableRouteMapForm(config) {
            return {
                routeUrl: config.routeUrl,
                nodes: config.nodes || [],
                routeText: config.initialRouteText || '',
                startNodeId: String(config.initialStartId || ''),
                endNodeId: String(config.initialEndId || ''),
                cableType: config.initialCableType || 'distribution',
                snapToRoads: @js(old('snap_to_roads', $routeText === '' ? '1' : '0') == '1'),
                waypoints: [],
                map: null,
                routeLayer: null,
                endpointLayer: null,
                markerLayer: null,
                markers: [],

                cableColors: {
                    backbone: '#D32F2F',
                    distribution: '#1565C0',
                    drop: '#2E7D32',
                },

                get routeColor() {
                    return this.cableColors[this.cableType] || '#1565C0';
                },

                async init() {
                    this.parseRouteText();
                    this.$nextTick(() => this.initMap());
                },

                initMap() {
                    const el = document.getElementById('cable-route-map');
                    if (!el || this.map) return;

                    const center = this.waypoints[0] || this.endpointNode(this.startNodeId) || { lat: -6.176, lng: 106.866 };
                    this.map = L.map(el, { zoomControl: true }).setView([center.lat, center.lng], 15);
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: 'Esri',
                        maxZoom: 19,
                    }).addTo(this.map);

                    this.routeLayer = L.layerGroup().addTo(this.map);
                    this.endpointLayer = L.layerGroup().addTo(this.map);
                    this.markerLayer = L.layerGroup().addTo(this.map);

                    this.map.on('click', (event) => this.addPoint(event.latlng.lat, event.latlng.lng));
                    this.refreshMap();
                    setTimeout(() => this.map.invalidateSize(), 250);
                },

                endpointNode(id) {
                    return this.nodes.find((node) => String(node.id) === String(id)) || null;
                },

                parseRouteText() {
                    const lines = (this.routeText || '').split(/\r?\n/);
                    const points = [];

                    lines.forEach((line) => {
                        const trimmed = line.trim();
                        if (!trimmed) return;
                        const parts = trimmed.split(/[,;]/).map((p) => p.trim());
                        if (parts.length < 2) return;
                        const lat = parseFloat(parts[0]);
                        const lng = parseFloat(parts[1]);
                        if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
                            points.push({ lat, lng });
                        }
                    });

                    this.waypoints = points;
                    this.refreshMap();
                },

                syncTextFromWaypoints() {
                    this.routeText = this.waypoints
                        .map((point) => `${Number(point.lat).toFixed(8)}, ${Number(point.lng).toFixed(8)}`)
                        .join('\n');
                },

                addPoint(lat, lng, refresh = true) {
                    this.waypoints.push({ lat: Number(lat), lng: Number(lng) });
                    this.syncTextFromWaypoints();
                    if (refresh) this.refreshMap();
                },

                undoLastPoint() {
                    if (!this.waypoints.length) return;
                    this.waypoints.pop();
                    this.syncTextFromWaypoints();
                    this.refreshMap();
                },

                clearRoute() {
                    this.waypoints = [];
                    this.syncTextFromWaypoints();
                    this.refreshMap();
                },

                loadFromEndpoints() {
                    const start = this.endpointNode(this.startNodeId);
                    const end = this.endpointNode(this.endNodeId);
                    this.waypoints = [];
                    if (start) this.waypoints.push({ lat: start.lat, lng: start.lng });
                    if (end) this.waypoints.push({ lat: end.lat, lng: end.lng });
                    this.syncTextFromWaypoints();
                    this.refreshMap();
                    if (this.snapToRoads) this.snapRouteToRoads();
                },

                onEndpointChange() {
                    this.refreshEndpoints();
                    if (this.snapToRoads && this.waypoints.length === 0) {
                        this.loadFromEndpoints();
                    }
                },

                onSnapToggle() {
                    if (this.snapToRoads && this.waypoints.length >= 2) {
                        this.snapRouteToRoads();
                    } else if (this.snapToRoads && this.waypoints.length === 0) {
                        this.loadFromEndpoints();
                    } else {
                        this.refreshMap();
                    }
                },

                async snapRouteToRoads() {
                    let points = [...this.waypoints];
                    if (points.length < 2) {
                        const start = this.endpointNode(this.startNodeId);
                        const end = this.endpointNode(this.endNodeId);
                        if (start && end) {
                            points = [{ lat: start.lat, lng: start.lng }, { lat: end.lat, lng: end.lng }];
                        }
                    }

                    if (points.length < 2) return;

                    const params = new URLSearchParams();
                    points.forEach((point, index) => {
                        params.append(`waypoints[${index}][lat]`, point.lat);
                        params.append(`waypoints[${index}][lng]`, point.lng);
                    });

                    try {
                        const response = await fetch(`${this.routeUrl}?${params.toString()}`, { credentials: 'same-origin' });
                        if (!response.ok) return;
                        const data = await response.json();
                        if (!Array.isArray(data.path) || data.path.length < 2) return;

                        this.waypoints = data.path.map((pair) => ({ lat: pair[0], lng: pair[1] }));
                        this.syncTextFromWaypoints();
                        this.refreshMap(true);
                    } catch (error) {
                        // keep current waypoints
                    }
                },

                refreshRouteStyle() {
                    this.refreshMap();
                },

                refreshEndpoints() {
                    if (!this.endpointLayer) return;
                    this.endpointLayer.clearLayers();

                    const start = this.endpointNode(this.startNodeId);
                    const end = this.endpointNode(this.endNodeId);

                    if (start) {
                        L.circleMarker([start.lat, start.lng], {
                            radius: 10, color: '#2E7D32', fillColor: '#2E7D32', fillOpacity: 0.95, weight: 2,
                        }).addTo(this.endpointLayer).bindPopup(`START: ${start.code}`);
                    }

                    if (end) {
                        L.circleMarker([end.lat, end.lng], {
                            radius: 10, color: '#C62828', fillColor: '#C62828', fillOpacity: 0.95, weight: 2,
                        }).addTo(this.endpointLayer).bindPopup(`END: ${end.code}`);
                    }
                },

                refreshMap(previewOnly = false) {
                    if (!this.map) return;

                    this.markerLayer.clearLayers();
                    this.routeLayer.clearLayers();
                    this.markers = [];
                    this.refreshEndpoints();

                    this.waypoints.forEach((point, index) => {
                        const marker = L.circleMarker([point.lat, point.lng], {
                            radius: 7,
                            color: '#1565C0',
                            fillColor: '#1565C0',
                            fillOpacity: 0.9,
                            weight: 2,
                            draggable: false,
                        }).addTo(this.markerLayer).bindPopup(`#${index + 1}`);

                        marker.on('click', (event) => {
                            L.DomEvent.stopPropagation(event);
                        });

                        marker.on('mousedown', () => {
                            const onMove = (moveEvent) => {
                                marker.setLatLng(moveEvent.latlng);
                                this.waypoints[index] = {
                                    lat: moveEvent.latlng.lat,
                                    lng: moveEvent.latlng.lng,
                                };
                                this.syncTextFromWaypoints();
                                this.drawPolyline();
                            };
                            const onUp = () => {
                                this.map.off('mousemove', onMove);
                                this.map.off('mouseup', onUp);
                            };
                            this.map.on('mousemove', onMove);
                            this.map.on('mouseup', onUp);
                        });

                        this.markers.push(marker);
                    });

                    this.drawPolyline(previewOnly);

                    if (this.waypoints.length > 0) {
                        const bounds = L.latLngBounds(this.waypoints.map((p) => [p.lat, p.lng]));
                        this.endpointLayer.eachLayer((layer) => bounds.extend(layer.getLatLng()));
                        this.map.fitBounds(bounds, { padding: [30, 30], maxZoom: 17 });
                    }
                },

                drawPolyline(previewOnly = false) {
                    if (!this.routeLayer) return;
                    this.routeLayer.clearLayers();

                    if (this.waypoints.length < 2) return;

                    const path = this.waypoints.map((p) => [p.lat, p.lng]);
                    L.polyline(path, {
                        color: this.routeColor,
                        weight: 5,
                        opacity: 0.9,
                        dashArray: previewOnly ? null : '10,6',
                    }).addTo(this.routeLayer);
                },
            };
        }
    </script>
</x-app-layout>
