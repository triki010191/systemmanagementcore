<x-app-layout>
    @php
        $brandName = $cmsSettings['branding']['app_name'] ?? config('app.name', 'HFNMS');
        $companyShort = strtoupper(substr($brandName, 0, 3));
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-md w-full">
            <div>
                <h2 class="font-semibold text-headline-md text-on-surface">{{ __('hfnms.gis_map') }}</h2>
                <p class="text-body-sm text-on-surface-variant">{{ __('hfnms.gis_map_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-sm text-body-sm text-on-surface-variant">
                <span class="inline-flex items-center gap-xs px-md py-xs rounded-full bg-surface-container-low border border-outline-variant">
                    <span class="material-symbols-outlined text-[16px]">location_on</span>
                    <strong>{{ $mapData['stats']['nodes'] }}</strong> {{ __('hfnms.nodes_on_map') }}
                </span>
                <span class="inline-flex items-center gap-xs px-md py-xs rounded-full bg-surface-container-low border border-outline-variant">
                    <span class="material-symbols-outlined text-[16px]">cable</span>
                    <strong>{{ $mapData['stats']['cables'] }}</strong> {{ __('hfnms.cables_on_map') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col -mx-md sm:-mx-lg -my-md sm:-my-lg h-[calc(100dvh-4rem)] min-h-[420px]">
        <div class="relative flex-1 min-h-0" x-data="{ panelOpen: false }">
            <div id="gis-map" class="absolute inset-0 z-0"></div>

            {{-- Mobile: backdrop when panel open --}}
            <div
                x-show="panelOpen"
                x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="panelOpen = false"
                class="lg:hidden fixed inset-0 bg-black/40 z-[490]"
                x-cloak
            ></div>

            {{-- Mobile: floating button to open panel --}}
            <button
                type="button"
                @click="panelOpen = true"
                x-show="!panelOpen"
                class="lg:hidden absolute bottom-md right-md z-[500] flex items-center gap-xs px-md py-sm rounded-full bg-primary text-on-primary shadow-lg font-semibold text-body-sm"
                x-cloak
            >
                <span class="material-symbols-outlined text-[20px]">layers</span>
                {{ __('hfnms.gis_show_panel') }}
            </button>

            {{-- Layer controls --}}
            <div
                class="absolute top-md left-md right-md sm:right-auto z-[500] w-auto sm:w-[280px] max-w-[280px] max-h-[calc(100%-2rem)] overflow-y-auto custom-scrollbar"
                :class="panelOpen ? 'block' : 'hidden lg:block'"
            >
                <div class="bg-white/95 backdrop-blur-sm rounded-lg border border-outline-variant shadow-lg p-md space-y-md">
                    <div class="flex items-center justify-between gap-sm lg:hidden">
                        <p class="text-label-caps text-on-surface-variant">{{ __('hfnms.gis_panel_title') }}</p>
                        <button
                            type="button"
                            @click="panelOpen = false"
                            class="p-xs rounded hover:bg-surface-container-low text-on-surface-variant"
                            aria-label="{{ __('hfnms.gis_hide_panel') }}"
                        >
                            <span class="material-symbols-outlined text-[20px]">close</span>
                        </button>
                    </div>

                    <div>
                        <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.gis_base_map') }}</p>
                        <div class="grid grid-cols-2 gap-xs">
                            <button type="button" data-base="satellite"
                                    class="gis-base-btn active px-sm py-xs rounded-md text-body-sm font-medium border border-primary bg-primary/10 text-primary">
                                {{ __('hfnms.gis_satellite') }}
                            </button>
                            <button type="button" data-base="street"
                                    class="gis-base-btn px-sm py-xs rounded-md text-body-sm font-medium border border-outline-variant text-on-surface-variant hover:bg-surface-container-low">
                                {{ __('hfnms.gis_street') }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.gis_layers') }}</p>
                        <div class="space-y-xs">
                            <label class="flex items-center gap-sm text-body-sm cursor-pointer">
                                <input type="checkbox" id="layer-nodes" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                {{ __('hfnms.gis_layer_nodes') }}
                            </label>
                            <label class="flex items-center gap-sm text-body-sm cursor-pointer">
                                <input type="checkbox" id="layer-cables" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                {{ __('hfnms.gis_layer_cables') }}
                            </label>
                            <label class="flex items-center gap-sm text-body-sm cursor-pointer">
                                <input type="checkbox" id="layer-links" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                {{ __('hfnms.gis_layer_links') }}
                            </label>
                            <label class="flex items-center gap-sm text-body-sm cursor-pointer">
                                <input type="checkbox" id="layer-labels" checked class="rounded border-outline-variant text-primary focus:ring-primary">
                                {{ __('hfnms.gis_layer_labels') }}
                            </label>
                        </div>
                    </div>

                    <div>
                        <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.gis_filter_type') }}</p>
                        <select id="filter-node-type" class="w-full rounded-md border-outline-variant text-body-sm focus:border-primary focus:ring-primary">
                            <option value="">{{ __('hfnms.gis_all_types') }}</option>
                            @foreach ($mapData['legend']['node_types'] as $nodeType)
                                <option value="{{ $nodeType['key'] }}">{{ $nodeType['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.gis_cable_legend') }}</p>
                        <div class="space-y-xs">
                            @foreach ($mapData['legend']['cable_types'] as $item)
                                <div class="flex items-center gap-sm text-body-sm">
                                    <span class="w-8 h-1 rounded-full shrink-0" style="background: {{ $item['color'] }}"></span>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="text-label-caps text-on-surface-variant mb-sm">{{ __('hfnms.gis_link_legend') }}</p>
                        <div class="space-y-xs">
                            @foreach ($mapData['legend']['link_types'] as $item)
                                <div class="flex items-center gap-sm text-body-sm">
                                    <span class="w-8 h-0.5 shrink-0 border-t-2" style="border-color: {{ $item['color'] }}"></span>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

    <style>
        #gis-map { background: #1a1a2e; }
        .leaflet-container { font-family: Inter, system-ui, sans-serif; }

        .gis-marker-wrap {
            background: transparent !important;
            border: none !important;
        }

        .gis-device {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            pointer-events: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,.35));
        }

        .gis-device-box {
            width: 36px;
            height: 28px;
            border-radius: 4px;
            border: 2px solid rgba(255,255,255,.9);
            background: linear-gradient(145deg, #ffffff 0%, #e8eef5 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            flex-shrink: 0;
        }

        .gis-device-box .brand {
            position: absolute;
            top: 1px;
            left: 2px;
            font-size: 5px;
            font-weight: 700;
            color: #1565C0;
            letter-spacing: -.2px;
            line-height: 1;
        }

        .gis-device-box svg {
            width: 22px;
            height: 16px;
        }

        .gis-device-label {
            background: rgba(255,255,255,.92);
            border: 1px solid rgba(0,0,0,.12);
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 600;
            color: #1a1a2e;
            white-space: nowrap;
            max-width: 220px;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
            margin-top: 2px;
        }

        .gis-device-label small {
            display: block;
            font-size: 9px;
            font-weight: 500;
            color: #546E7A;
        }

        .gis-cable-label-wrap {
            background: transparent !important;
            border: none !important;
        }

        .gis-cable-label {
            background: rgba(255,255,255,.95);
            border: 1px solid rgba(0,0,0,.15);
            border-left: 3px solid var(--cable-color, #1565C0);
            border-radius: 3px;
            padding: 1px 6px;
            font-size: 10px;
            font-weight: 600;
            color: #263238;
            white-space: nowrap;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
            pointer-events: none;
        }

        .gis-popup strong { color: #1565C0; }
        .gis-popup .meta { font-size: 12px; color: #546E7A; margin-top: 4px; }

        .gis-base-btn.active {
            border-color: #1565C0;
            background: rgba(21,101,192,.1);
            color: #1565C0;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mapData = @json($mapData);
            const brandShort = @json($companyShort);

            const deviceSvgs = {
                pop: '<rect x="4" y="6" width="14" height="10" rx="1" fill="#1B2A41"/><rect x="6" y="8" width="3" height="2" fill="#64B5F6"/><rect x="10" y="8" width="3" height="2" fill="#64B5F6"/><rect x="14" y="8" width="2" height="6" fill="#455A64"/>',
                olt: '<rect x="3" y="5" width="16" height="12" rx="1" fill="#0D47A1"/><rect x="5" y="7" width="2" height="2" fill="#4FC3F7"/><rect x="8" y="7" width="2" height="2" fill="#4FC3F7"/><rect x="11" y="7" width="2" height="2" fill="#4FC3F7"/><rect x="14" y="7" width="2" height="2" fill="#81C784"/><rect x="5" y="11" width="11" height="1" fill="#1565C0"/>',
                otb: '<rect x="4" y="4" width="14" height="14" rx="1" fill="#455A64"/><line x1="6" y1="7" x2="16" y2="7" stroke="#90A4AE" stroke-width="1"/><line x1="6" y1="10" x2="16" y2="10" stroke="#90A4AE" stroke-width="1"/><line x1="6" y1="13" x2="16" y2="13" stroke="#90A4AE" stroke-width="1"/>',
                odc: '<rect x="3" y="3" width="16" height="16" rx="2" fill="#37474F"/><rect x="5" y="5" width="12" height="4" rx="1" fill="#546E7A"/><circle cx="7" cy="13" r="1.5" fill="#4FC3F7"/><circle cx="11" cy="13" r="1.5" fill="#4FC3F7"/><circle cx="15" cy="13" r="1.5" fill="#4FC3F7"/>',
                splitter: '<rect x="5" y="6" width="12" height="10" rx="1" fill="#6A1B9A"/><line x1="7" y1="11" x2="15" y2="11" stroke="#CE93D8" stroke-width="1.5"/><circle cx="8" cy="11" r="1" fill="#E1BEE7"/><circle cx="12" cy="11" r="1" fill="#E1BEE7"/><circle cx="16" cy="11" r="1" fill="#E1BEE7"/>',
                odp: '<rect x="4" y="5" width="14" height="12" rx="1" fill="#1565C0"/><rect x="6" y="7" width="2" height="2" rx=".3" fill="#BBDEFB"/><rect x="9" y="7" width="2" height="2" rx=".3" fill="#BBDEFB"/><rect x="12" y="7" width="2" height="2" rx=".3" fill="#BBDEFB"/><rect x="15" y="7" width="2" height="2" rx=".3" fill="#81C784"/><rect x="6" y="11" width="11" height="2" rx=".3" fill="#0D47A1"/>',
                customer: '<path d="M11 4 L6 10 H16 Z" fill="#2E7D32"/><rect x="7" y="10" width="8" height="6" fill="#388E3C"/><rect x="10" y="12" width="2" height="4" fill="#A5D6A7"/>',
                generic: '<rect x="5" y="6" width="12" height="10" rx="1" fill="#546E7A"/><line x1="8" y1="11" x2="14" y2="11" stroke="#CFD8DC" stroke-width="1.5"/>',
            };

            function buildDeviceIcon(node) {
                const svgContent = deviceSvgs[node.icon] || deviceSvgs.generic;
                const subtitle = node.subtitle ? `<small>${node.subtitle}</small>` : '';
                const html = `
                    <div class="gis-device" data-type="${node.type}">
                        <div class="gis-device-box" style="border-color: ${node.color}">
                            <span class="brand">${brandShort}</span>
                            <svg viewBox="0 0 22 16" xmlns="http://www.w3.org/2000/svg">${svgContent}</svg>
                        </div>
                        <div class="gis-device-label">${node.map_label}${subtitle}</div>
                    </div>`;

                return L.divIcon({
                    className: 'gis-marker-wrap',
                    html,
                    iconSize: [260, 36],
                    iconAnchor: [18, 18],
                });
            }

            function midpoint(path) {
                if (!path || path.length < 2) return null;
                if (path.length === 2) {
                    return [
                        (path[0][0] + path[1][0]) / 2,
                        (path[0][1] + path[1][1]) / 2,
                    ];
                }
                const midIdx = Math.floor(path.length / 2);
                return path[midIdx];
            }

            function cableLabelIcon(label, color) {
                return L.divIcon({
                    className: 'gis-cable-label-wrap',
                    html: `<div class="gis-cable-label" style="--cable-color:${color}">${label}</div>`,
                    iconSize: [1, 1],
                    iconAnchor: [0, 0],
                });
            }

            const map = L.map('gis-map', { zoomControl: true }).setView([-6.176, 106.866], 16);

            const satellite = L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                { attribution: 'Esri, Maxar', maxZoom: 19 }
            );

            const street = L.tileLayer(
                'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                { attribution: '&copy; OpenStreetMap', maxZoom: 19 }
            );

            satellite.addTo(map);
            let activeBase = satellite;

            const layers = {
                nodes: L.layerGroup().addTo(map),
                cables: L.layerGroup().addTo(map),
                links: L.layerGroup().addTo(map),
                labels: L.layerGroup().addTo(map),
            };

            const nodeMarkers = [];

            mapData.nodes.forEach(node => {
                const marker = L.marker([node.latitude, node.longitude], {
                    icon: buildDeviceIcon(node),
                });

                marker.bindPopup(`
                    <div class="gis-popup">
                        <strong>${node.map_label}</strong>
                        <div class="meta">${node.type_label} · ${node.status}</div>
                        ${node.subtitle ? `<div class="meta">${node.subtitle}</div>` : ''}
                        <div class="meta">${node.latitude.toFixed(6)}, ${node.longitude.toFixed(6)}</div>
                    </div>
                `);

                marker._gisNodeType = node.type;
                nodeMarkers.push(marker);
                marker.addTo(layers.nodes);
            });

            mapData.cables.forEach(cable => {
                if (!cable.path || cable.path.length < 2) return;

                const line = L.polyline(cable.path, {
                    color: cable.color,
                    weight: cable.weight,
                    opacity: 0.85,
                    lineCap: 'round',
                    lineJoin: 'round',
                });

                line.bindPopup(`
                    <div class="gis-popup">
                        <strong>${cable.code}</strong>
                        <div class="meta">${cable.name || ''}</div>
                        <div class="meta">${cable.core_count} core · ${cable.cable_type || '-'}</div>
                    </div>
                `);

                line.addTo(layers.cables);

                const center = midpoint(cable.path);
                if (center && cable.label) {
                    L.marker(center, {
                        icon: cableLabelIcon(cable.label, cable.color),
                        interactive: false,
                    }).addTo(layers.labels);
                }
            });

            mapData.links.forEach(link => {
                if (!link.path || link.path.length < 2) return;

                const options = {
                    color: link.color,
                    weight: link.weight,
                    opacity: 0.75,
                    lineCap: 'round',
                };

                if (link.dash) {
                    options.dashArray = link.dash.join(',');
                }

                const line = L.polyline(link.path, options);
                line.bindPopup(`<div class="gis-popup"><strong>${link.label}</strong><div class="meta">${link.link_type.replace(/_/g, ' ')}</div></div>`);
                line.addTo(layers.links);

                const center = midpoint(link.path);
                if (center && link.label) {
                    L.marker(center, {
                        icon: cableLabelIcon(link.label, link.color),
                        interactive: false,
                    }).addTo(layers.labels);
                }
            });

            if (layers.nodes.getLayers().length) {
                const bounds = L.featureGroup([
                    ...layers.nodes.getLayers(),
                    ...layers.cables.getLayers(),
                ]).getBounds();
                if (bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.15));
                }
            }

            L.control.scale({ imperial: false }).addTo(map);

            document.querySelectorAll('.gis-base-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.gis-base-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    map.removeLayer(activeBase);
                    activeBase = btn.dataset.base === 'street' ? street : satellite;
                    activeBase.addTo(map);
                });
            });

            document.getElementById('layer-nodes').addEventListener('change', e => {
                e.target.checked ? map.addLayer(layers.nodes) : map.removeLayer(layers.nodes);
            });
            document.getElementById('layer-cables').addEventListener('change', e => {
                e.target.checked ? map.addLayer(layers.cables) : map.removeLayer(layers.cables);
            });
            document.getElementById('layer-links').addEventListener('change', e => {
                e.target.checked ? map.addLayer(layers.links) : map.removeLayer(layers.links);
            });
            document.getElementById('layer-labels').addEventListener('change', e => {
                e.target.checked ? map.addLayer(layers.labels) : map.removeLayer(layers.labels);
            });

            document.getElementById('filter-node-type').addEventListener('change', e => {
                const type = e.target.value;
                nodeMarkers.forEach(marker => {
                    const visible = !type || marker._gisNodeType === type;
                    if (visible) {
                        if (map.hasLayer(layers.nodes)) marker.addTo(layers.nodes);
                    } else {
                        layers.nodes.removeLayer(marker);
                    }
                });
            });
        });
    </script>
</x-app-layout>
