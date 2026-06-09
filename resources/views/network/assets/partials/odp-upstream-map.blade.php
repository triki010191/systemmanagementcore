@if ($route)
    <div class="bg-surface-container-lowest border border-outline-variant rounded-lg p-lg">
        <div class="flex flex-wrap items-start justify-between gap-md mb-md">
            <div>
                <h3 class="text-label-caps text-on-surface-variant">{{ __('hfnms.odp_upstream_map') }}</h3>
                <p class="text-body-sm text-on-surface-variant mt-xs">{{ __('hfnms.odp_upstream_map_hint') }}</p>
            </div>
            <span class="inline-flex items-center gap-xs px-sm py-xs rounded-full bg-surface-container text-body-sm font-mono">
                {{ $route['label'] ?? $asset->networkNode->code }}
            </span>
        </div>

        <div id="odp-upstream-map" class="h-[320px] rounded-lg border border-outline-variant overflow-hidden"></div>

        <div class="mt-sm flex flex-wrap gap-sm text-[11px] text-on-surface-variant">
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#455A64]"></span> OTB</span>
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#37474F]"></span> ODC</span>
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#D84315]"></span> Join</span>
            <span class="inline-flex items-center gap-xs"><span class="w-3 h-3 rounded-full bg-[#1565C0]"></span> ODP</span>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const routeData = @json($route);
            const routeUrl = @json(route('gis.route'));
            const mapEl = document.getElementById('odp-upstream-map');
            if (!mapEl || !routeData?.path?.length) return;

            const map = L.map(mapEl, { zoomControl: true });
            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Esri',
                maxZoom: 19,
            }).addTo(map);

            const nodeColors = { otb: '#455A64', odc: '#37474F', odp: '#1565C0', joint: '#D84315' };

            async function resolvePath() {
                if (!Array.isArray(routeData.waypoints) || routeData.waypoints.length < 2) {
                    return routeData.path;
                }

                const params = new URLSearchParams();
                routeData.waypoints.forEach((point, index) => {
                    params.append(`waypoints[${index}][lat]`, point.lat);
                    params.append(`waypoints[${index}][lng]`, point.lng);
                });

                try {
                    const response = await fetch(`${routeUrl}?${params.toString()}`, { credentials: 'same-origin' });
                    if (!response.ok) return routeData.path;
                    const data = await response.json();
                    return Array.isArray(data.path) && data.path.length >= 2 ? data.path : routeData.path;
                } catch (error) {
                    return routeData.path;
                }
            }

            resolvePath().then((path) => {
                L.polyline(path, {
                    color: routeData.color || '#1565C0',
                    weight: 4,
                    opacity: 0.9,
                    dashArray: '8,6',
                }).addTo(map);

                (routeData.waypoints || []).forEach((point, index) => {
                    const type = point.type || (index === (routeData.waypoints.length - 1) ? 'odp' : 'odc');
                    const color = nodeColors[type] || '#546E7A';
                    L.circleMarker([point.lat, point.lng], {
                        radius: type === 'joint' ? 8 : 9,
                        color,
                        fillColor: color,
                        fillOpacity: 0.95,
                        weight: 2,
                    }).addTo(map).bindPopup(point.code || type.toUpperCase());
                });

                map.fitBounds(path, { padding: [30, 30], maxZoom: 17 });
            });
        });
    </script>
@endif
