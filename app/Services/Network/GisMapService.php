<?php

namespace App\Services\Network;

use App\Enums\NetworkLinkType;
use App\Enums\NetworkNodeType;
use App\Models\CoreJoint;
use App\Models\FiberCable;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use App\Models\Odp;
use Illuminate\Support\Collection;

class GisMapService
{
    /** @return array{nodes: list<array<string, mixed>>, cables: list<array<string, mixed>>, links: list<array<string, mixed>>, joints: list<array<string, mixed>>, odp_routes: list<array<string, mixed>>, legend: array<string, mixed>, stats: array<string, int>} */
    public function getMapPayload(): array
    {
        $nodes = $this->loadNodes();
        $nodeIds = $nodes->pluck('id');

        return [
            'nodes' => $nodes->map(fn (NetworkNode $node) => $this->formatNode($node))->values()->all(),
            'cables' => $this->loadCables($nodeIds)->map(fn (FiberCable $cable) => $this->formatCable($cable))->values()->all(),
            'links' => $this->loadLinks($nodeIds)
                ->map(fn (NetworkLink $link) => $this->formatLink($link))
                ->filter(fn (array $link) => $link !== [])
                ->values()
                ->all(),
            'joints' => $this->loadJoints()
                ->map(fn (CoreJoint $joint) => $this->formatJoint($joint))
                ->filter(fn (array $joint) => $joint !== [])
                ->values()
                ->all(),
            'odp_routes' => $this->loadOdpUpstreamRoutes($nodeIds)
                ->map(fn (Odp $odp) => $this->formatOdpUpstreamRoute($odp))
                ->filter(fn (array $route) => $route !== [])
                ->values()
                ->all(),
            'legend' => $this->legendConfig(),
            'stats' => [
                'nodes' => $nodes->count(),
                'cables' => $this->loadCables($nodeIds)->count(),
                'links' => $this->loadLinks($nodeIds)->count(),
                'joints' => $this->loadJoints()->count(),
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public function getOdpUpstreamPayload(Odp $odp): ?array
    {
        $route = $this->formatOdpUpstreamRoute($odp);

        return $route !== [] ? $route : null;
    }

    /** @return Collection<int, NetworkNode> */
    private function loadNodes(): Collection
    {
        return NetworkNode::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with([
                'pop',
                'olt',
                'otb',
                'odc',
                'odp.customerConnections',
                'customer',
            ])
            ->orderBy('type')
            ->orderBy('code')
            ->get();
    }

    /** @param Collection<int, int|string> $nodeIds */
    private function loadCables(Collection $nodeIds): Collection
    {
        return FiberCable::query()
            ->whereNotNull('start_node_id')
            ->whereNotNull('end_node_id')
            ->where(function ($query) use ($nodeIds) {
                $query->whereIn('start_node_id', $nodeIds)
                    ->whereIn('end_node_id', $nodeIds);
            })
            ->with(['startNode:id,latitude,longitude,code,name', 'endNode:id,latitude,longitude,code,name'])
            ->get();
    }

    /** @param Collection<int, int|string> $nodeIds */
    private function loadLinks(Collection $nodeIds): Collection
    {
        return NetworkLink::query()
            ->whereIn('source_node_id', $nodeIds)
            ->whereIn('target_node_id', $nodeIds)
            ->with([
                'sourceNode:id,latitude,longitude,code,name,type',
                'targetNode:id,latitude,longitude,code,name,type',
                'cable:id,code,name,cable_type,core_count,metadata',
                'tubeColor:id,color_name,hex_code',
            ])
            ->get();
    }

    /** @return array<string, mixed> */
    private function formatNode(NetworkNode $node): array
    {
        $type = $node->type instanceof NetworkNodeType ? $node->type->value : (string) $node->type;

        return [
            'id' => $node->id,
            'type' => $type,
            'type_label' => $node->type instanceof NetworkNodeType ? $node->type->label() : strtoupper($type),
            'code' => $node->code,
            'name' => $node->name,
            'latitude' => (float) $node->latitude,
            'longitude' => (float) $node->longitude,
            'status' => $node->status?->value ?? (string) $node->status,
            'map_label' => $this->buildNodeMapLabel($node),
            'subtitle' => $this->buildNodeSubtitle($node),
            'icon' => $this->nodeIconKey($node),
            'color' => $this->nodeColor($type),
        ];
    }

    /** @return array<string, mixed> */
    private function formatCable(FiberCable $cable): array
    {
        $geometry = $cable->route_geometry;
        $hasCustomGeometry = is_array($geometry) && count($geometry) >= 2;
        $path = $this->resolveCablePath($cable);
        $color = $this->resolveCableColor($cable);

        return [
            'id' => $cable->id,
            'code' => $cable->code,
            'name' => $cable->name,
            'cable_type' => $cable->cable_type,
            'core_count' => $cable->core_count,
            'status' => $cable->status,
            'color' => $color,
            'label' => $this->buildCableLabel($cable),
            'path' => $path,
            'straight_line' => ! $hasCustomGeometry && count($path) === 2,
            'endpoints' => ($cable->startNode && $cable->endNode) ? [
                'start' => [(float) $cable->startNode->latitude, (float) $cable->startNode->longitude],
                'end' => [(float) $cable->endNode->latitude, (float) $cable->endNode->longitude],
            ] : null,
            'weight' => $this->cableLineWeight($cable),
        ];
    }

    /** @return Collection<int, CoreJoint> */
    private function loadJoints(): Collection
    {
        return CoreJoint::query()
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['sourceNode.parent', 'targetNode', 'networkNode'])
            ->get();
    }

    /** @return Collection<int, Odp> */
    private function loadOdpUpstreamRoutes(Collection $nodeIds): Collection
    {
        return Odp::query()
            ->whereHas('networkNode', function ($query) use ($nodeIds) {
                $query->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->whereIn('id', $nodeIds);
            })
            ->with(['networkNode', 'odc.networkNode.parent'])
            ->get();
    }

    /** @return array<string, mixed> */
    private function formatOdpUpstreamRoute(Odp $odp): array
    {
        $odpNode = $odp->networkNode;
        if (! $odpNode?->latitude || ! $odpNode?->longitude) {
            return [];
        }

        $joint = CoreJoint::query()
            ->where('joint_type', 'odc_odp')
            ->where('target_node_id', $odpNode->id)
            ->where('status', 'active')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['sourceNode.parent'])
            ->first();

        if ($joint) {
            $route = $this->buildJointRouteData($joint);
            if ($route === null) {
                return [];
            }

            return array_merge($route, [
                'odp_id' => $odp->id,
                'odp_code' => $odpNode->code,
                'label' => sprintf('%s ← %s', $odpNode->code, $joint->code),
            ]);
        }

        $odcNode = $odp->odc?->networkNode;
        if (! $odcNode?->latitude || ! $odcNode?->longitude) {
            return [];
        }

        $waypoints = $this->buildOdpOdcWaypoints($odpNode, $odcNode);
        $pathResult = $this->buildChainedPath($waypoints);

        return [
            'odp_id' => $odp->id,
            'odp_code' => $odpNode->code,
            'label' => sprintf('%s ← %s', $odpNode->code, $odcNode->code),
            'path' => $pathResult['path'],
            'waypoints' => $pathResult['waypoints'],
            'straight_line' => $pathResult['straight_line'],
            'color' => '#1565C0',
        ];
    }

    /** @return array<string, mixed> */
    private function formatJoint(CoreJoint $joint): array
    {
        if (! $joint->latitude || ! $joint->longitude) {
            return [];
        }

        $route = $this->buildJointRouteData($joint);
        if ($route === null) {
            return [
                'id' => $joint->id,
                'code' => $joint->code,
                'latitude' => (float) $joint->latitude,
                'longitude' => (float) $joint->longitude,
                'label' => $joint->code.($joint->core_number ? ' · C'.$joint->core_number : ''),
                'joint_type' => $joint->joint_type?->value,
                'joint_type_label' => $joint->joint_type?->label(),
                'source_code' => $joint->sourceNode?->code,
                'target_code' => $joint->targetNode?->code,
                'core_number' => $joint->core_number,
                'routes' => [],
            ];
        }

        return [
            'id' => $joint->id,
            'code' => $joint->code,
            'latitude' => (float) $joint->latitude,
            'longitude' => (float) $joint->longitude,
            'label' => $joint->code.($joint->core_number ? ' · C'.$joint->core_number : ''),
            'joint_type' => $joint->joint_type?->value,
            'joint_type_label' => $joint->joint_type?->label(),
            'source_code' => $joint->sourceNode?->code,
            'target_code' => $joint->targetNode?->code,
            'core_number' => $joint->core_number,
            'routes' => [[
                'path' => $route['path'],
                'waypoints' => $route['waypoints'],
                'straight_line' => $route['straight_line'],
                'color' => $route['color'],
                'label' => sprintf(
                    '%s · Core %s',
                    $joint->joint_type?->label() ?? 'Join',
                    $joint->core_number ?? '-',
                ),
            ]],
        ];
    }

    /** @return array{path: list<array{0: float, 1: float}>, waypoints: list<array{lat: float, lng: float, code: string, type: string}>, straight_line: bool, color: string}|null */
    private function buildJointRouteData(CoreJoint $joint): ?array
    {
        if (! $joint->sourceNode || ! $joint->targetNode) {
            return null;
        }

        $jointPoint = [(float) $joint->latitude, (float) $joint->longitude];
        $color = $joint->joint_type?->value === 'odc_odp' ? '#E65100' : '#1565C0';
        $waypoints = [];

        if ($joint->joint_type?->value === 'odc_odp') {
            $otbNode = $joint->sourceNode->parent;
            $odcNode = $joint->sourceNode;
            $odpNode = $joint->targetNode;

            if ($otbNode?->latitude && $otbNode?->longitude) {
                $waypoints[] = $this->waypointFromNode($otbNode);
            }

            if ($odcNode->latitude && $odcNode->longitude) {
                $waypoints[] = $this->waypointFromNode($odcNode);
            }

            $waypoints[] = [
                'lat' => $jointPoint[0],
                'lng' => $jointPoint[1],
                'code' => $joint->code,
                'type' => 'joint',
            ];

            if ($odpNode->latitude && $odpNode->longitude) {
                $waypoints[] = $this->waypointFromNode($odpNode);
            }
        } else {
            $waypoints[] = $this->waypointFromNode($joint->sourceNode);
            $waypoints[] = [
                'lat' => $jointPoint[0],
                'lng' => $jointPoint[1],
                'code' => $joint->code,
                'type' => 'joint',
            ];
            $waypoints[] = $this->waypointFromNode($joint->targetNode);
        }

        if (count($waypoints) < 2) {
            return null;
        }

        $pathResult = $this->buildChainedPath($waypoints);

        return [
            'path' => $pathResult['path'],
            'waypoints' => $pathResult['waypoints'],
            'straight_line' => $pathResult['straight_line'],
            'color' => $color,
        ];
    }

    /**
     * @return list<array{lat: float, lng: float, code: string, type: string, node_id?: int}>
     */
    private function buildOdpOdcWaypoints(NetworkNode $odpNode, NetworkNode $odcNode): array
    {
        $waypoints = [];

        $otbNode = $odcNode->parent;
        if ($otbNode?->latitude && $otbNode?->longitude) {
            $waypoints[] = $this->waypointFromNode($otbNode);
        }

        $waypoints[] = $this->waypointFromNode($odcNode);
        $waypoints[] = $this->waypointFromNode($odpNode);

        return $waypoints;
    }

    /** @return array{lat: float, lng: float, code: string, type: string, node_id: int} */
    private function waypointFromNode(NetworkNode $node): array
    {
        return [
            'lat' => (float) $node->latitude,
            'lng' => (float) $node->longitude,
            'code' => $node->code,
            'type' => $node->type instanceof NetworkNodeType ? $node->type->value : (string) $node->type,
            'node_id' => $node->id,
        ];
    }

    /**
     * @param  list<array{lat: float, lng: float, code: string, type: string, node_id?: int}>  $waypoints
     * @return array{path: list<array{0: float, 1: float}>, waypoints: list<array{lat: float, lng: float}>, straight_line: bool}
     */
    private function buildChainedPath(array $waypoints): array
    {
        $leafletWaypoints = collect($waypoints)
            ->map(fn (array $point) => [$point['lat'], $point['lng']])
            ->all();

        $mergedPath = [];
        $allStraight = true;

        for ($index = 0; $index < count($waypoints) - 1; $index++) {
            $from = $waypoints[$index];
            $to = $waypoints[$index + 1];
            $fromNodeId = $from['node_id'] ?? null;
            $toNodeId = $to['node_id'] ?? null;

            if ($fromNodeId && $toNodeId) {
                $segment = $this->resolveCablePathBetweenNodes((int) $fromNodeId, (int) $toNodeId);
            } else {
                $segment = [
                    [$from['lat'], $from['lng']],
                    [$to['lat'], $to['lng']],
                ];
            }

            if (count($segment) === 2
                && abs($segment[0][0] - $segment[1][0]) < 0.000001
                && abs($segment[0][1] - $segment[1][1]) < 0.000001) {
                continue;
            }

            if (count($segment) <= 2) {
                $allStraight = $allStraight && count($segment) === 2;
            } else {
                $allStraight = false;
            }

            if ($index > 0 && $segment !== []) {
                array_shift($segment);
            }

            $mergedPath = array_merge($mergedPath, $segment);
        }

        if ($mergedPath === []) {
            $mergedPath = $leafletWaypoints;
            $allStraight = true;
        }

        return [
            'path' => $mergedPath,
            'waypoints' => collect($waypoints)
                ->map(fn (array $point) => [
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'code' => $point['code'] ?? '',
                    'type' => $point['type'] ?? 'node',
                ])
                ->values()
                ->all(),
            'straight_line' => $allStraight,
        ];
    }

    /** @return list<array{0: float, 1: float}> */
    private function resolveCablePathBetweenNodes(int $fromNodeId, int $toNodeId): array
    {
        $cable = FiberCable::query()
            ->where(function ($query) use ($fromNodeId, $toNodeId) {
                $query->where(function ($inner) use ($fromNodeId, $toNodeId) {
                    $inner->where('start_node_id', $fromNodeId)
                        ->where('end_node_id', $toNodeId);
                })->orWhere(function ($inner) use ($fromNodeId, $toNodeId) {
                    $inner->where('start_node_id', $toNodeId)
                        ->where('end_node_id', $fromNodeId);
                });
            })
            ->with(['startNode:id,latitude,longitude', 'endNode:id,latitude,longitude'])
            ->first();

        if ($cable) {
            $path = $this->resolveCablePath($cable);
            if (count($path) >= 2) {
                if ((int) $cable->start_node_id === $fromNodeId) {
                    return $path;
                }

                return array_reverse($path);
            }
        }

        $from = NetworkNode::query()->select('latitude', 'longitude')->find($fromNodeId);
        $to = NetworkNode::query()->select('latitude', 'longitude')->find($toNodeId);

        if (! $from?->latitude || ! $from?->longitude || ! $to?->latitude || ! $to?->longitude) {
            return [];
        }

        return [
            [(float) $from->latitude, (float) $from->longitude],
            [(float) $to->latitude, (float) $to->longitude],
        ];
    }

    /** @return array<string, mixed> */
    private function formatLink(NetworkLink $link): array
    {
        $source = $link->sourceNode;
        $target = $link->targetNode;

        if (! $source || ! $target) {
            return [];
        }

        $linkType = $link->link_type instanceof NetworkLinkType
            ? $link->link_type->value
            : (string) $link->link_type;

        $color = $link->tubeColor?->hex_code
            ?? ($link->cable ? $this->resolveCableColor($link->cable) : null)
            ?? $this->linkTypeColor($linkType);

        return [
            'id' => $link->id,
            'link_type' => $linkType,
            'label' => $this->buildLinkLabel($link),
            'color' => $color,
            'weight' => $this->linkLineWeight($linkType),
            'dash' => $this->linkDashPattern($linkType),
            'path' => [
                [(float) $source->latitude, (float) $source->longitude],
                [(float) $target->latitude, (float) $target->longitude],
            ],
        ];
    }

    private function buildNodeMapLabel(NetworkNode $node): string
    {
        $typeLabel = $node->type instanceof NetworkNodeType ? $node->type->label() : strtoupper((string) $node->type);
        $code = $this->shortCode($node->code);
        $name = $node->name;

        if ($node->type === NetworkNodeType::Odp && $node->odp) {
            $portInfo = "Port {$node->odp->port_used}/{$node->odp->port_capacity}";

            return trim("{$typeLabel} {$code} {$name} - {$portInfo}");
        }

        if ($node->type === NetworkNodeType::Olt && $node->olt) {
            $model = trim(($node->olt->brand ?? '').' '.($node->olt->model ?? ''));

            return trim("{$typeLabel} {$code} {$name}".($model ? " - {$model}" : ''));
        }

        return trim("{$typeLabel} {$code} {$name}");
    }

    private function buildNodeSubtitle(NetworkNode $node): ?string
    {
        return match ($node->type) {
            NetworkNodeType::Odp => $node->odp
                ? "Port {$node->odp->port_used}/{$node->odp->port_capacity} · Core {$node->odp->cores_from_odc}"
                : null,
            NetworkNodeType::Odc => $node->odc
                ? "OTB→ODC {$node->odc->cores_from_otb} · ODC→ODP {$node->odc->cores_to_odp}"
                : null,
            NetworkNodeType::Customer => $node->customer?->service_type,
            default => null,
        };
    }

    private function shortCode(?string $code): string
    {
        if (! $code) {
            return '';
        }

        if (preg_match('/(\d+)$/', $code, $matches)) {
            return ltrim($matches[1], '0') ?: $matches[1];
        }

        return $code;
    }

    private function nodeIconKey(NetworkNode $node): string
    {
        $type = $node->type instanceof NetworkNodeType ? $node->type->value : (string) $node->type;

        return match ($type) {
            'pop' => 'pop',
            'olt' => 'olt',
            'otb' => 'otb',
            'odc' => 'odc',
            'odp' => 'odp',
            'customer' => 'customer',
            default => 'generic',
        };
    }

    private function nodeColor(string $type): string
    {
        return match ($type) {
            'pop' => '#1B2A41',
            'olt' => '#0D47A1',
            'otb' => '#455A64',
            'odc' => '#37474F',
            'odp' => '#1565C0',
            'customer' => '#2E7D32',
            default => '#546E7A',
        };
    }

    /** @return list<array{0: float, 1: float}> */
    private function resolveCablePath(FiberCable $cable): array
    {
        $geometry = $cable->route_geometry;

        if (is_array($geometry) && count($geometry) >= 2) {
            $path = [];

            foreach ($geometry as $point) {
                if (is_array($point) && isset($point['lat'], $point['lng'])) {
                    $path[] = [(float) $point['lat'], (float) $point['lng']];
                } elseif (is_array($point) && count($point) >= 2) {
                    $path[] = [(float) $point[0], (float) $point[1]];
                }
            }

            if (count($path) >= 2) {
                return $path;
            }
        }

        if ($cable->startNode && $cable->endNode) {
            return [
                [(float) $cable->startNode->latitude, (float) $cable->startNode->longitude],
                [(float) $cable->endNode->latitude, (float) $cable->endNode->longitude],
            ];
        }

        return [];
    }

    private function resolveCableColor(FiberCable $cable): string
    {
        $metadataColor = data_get($cable->metadata, 'map_color');

        if (is_string($metadataColor) && $metadataColor !== '') {
            return $metadataColor;
        }

        return match ($cable->cable_type) {
            'backbone' => '#D32F2F',
            'feeder' => '#EF6C00',
            'distribution' => '#1565C0',
            'drop' => '#2E7D32',
            'aerial' => '#00838F',
            default => match (true) {
                ($cable->core_count ?? 0) >= 48 => '#C62828',
                ($cable->core_count ?? 0) >= 12 => '#1565C0',
                default => '#546E7A',
            },
        };
    }

    private function buildCableLabel(FiberCable $cable): string
    {
        $parts = array_filter([
            $cable->code,
            $cable->core_count ? "{$cable->core_count}C" : null,
            $cable->cable_type ? strtoupper((string) $cable->cable_type) : null,
        ]);

        return implode(' · ', $parts);
    }

    private function buildLinkLabel(NetworkLink $link): string
    {
        if ($link->cable) {
            $core = $link->core_number ? " Core {$link->core_number}" : '';

            return $link->cable->code.$core;
        }

        $linkType = $link->link_type instanceof NetworkLinkType
            ? $link->link_type
            : NetworkLinkType::tryFrom((string) $link->link_type);

        return match ($linkType) {
            NetworkLinkType::Drop => 'Drop',
            NetworkLinkType::PatchCord => 'Patch',
            NetworkLinkType::Splice => 'Splice',
            default => strtoupper(str_replace('_', ' ', (string) $link->link_type)),
        };
    }

    private function cableLineWeight(FiberCable $cable): int
    {
        return match (true) {
            ($cable->core_count ?? 0) >= 48 => 6,
            ($cable->core_count ?? 0) >= 12 => 5,
            default => 4,
        };
    }

    private function linkTypeColor(string $linkType): string
    {
        return match ($linkType) {
            'fiber_cable' => '#1565C0',
            'drop' => '#00C853',
            'patch_cord' => '#F9A825',
            'splice' => '#78909C',
            default => '#607D8B',
        };
    }

    private function linkLineWeight(string $linkType): int
    {
        return match ($linkType) {
            'fiber_cable' => 4,
            'drop' => 3,
            'patch_cord' => 2,
            default => 2,
        };
    }

    /** @return list<int>|null */
    private function linkDashPattern(string $linkType): ?array
    {
        return match ($linkType) {
            'patch_cord' => [6, 4],
            'drop' => [8, 6],
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function legendConfig(): array
    {
        return [
            'cable_types' => [
                ['key' => 'backbone', 'label' => 'Backbone', 'color' => '#D32F2F'],
                ['key' => 'feeder', 'label' => 'Feeder', 'color' => '#EF6C00'],
                ['key' => 'distribution', 'label' => 'Distribution', 'color' => '#1565C0'],
                ['key' => 'drop', 'label' => 'Drop', 'color' => '#2E7D32'],
            ],
            'link_types' => [
                ['key' => 'fiber_cable', 'label' => 'Fiber Link', 'color' => '#1565C0'],
                ['key' => 'drop', 'label' => 'Drop Cable', 'color' => '#00C853'],
                ['key' => 'patch_cord', 'label' => 'Patch Cord', 'color' => '#F9A825'],
                ['key' => 'joint_route', 'label' => 'Join OTB→ODC→Join→ODP', 'color' => '#E65100'],
                ['key' => 'odp_upstream', 'label' => 'ODP → Join / ODC', 'color' => '#1565C0'],
            ],
            'node_types' => collect(NetworkNodeType::cases())->map(fn (NetworkNodeType $type) => [
                'key' => $type->value,
                'label' => $type->label(),
                'color' => $this->nodeColor($type->value),
            ])->values()->all(),
        ];
    }
}
