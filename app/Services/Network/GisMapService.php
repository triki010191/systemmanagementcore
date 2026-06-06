<?php

namespace App\Services\Network;

use App\Enums\NetworkLinkType;
use App\Enums\NetworkNodeType;
use App\Models\FiberCable;
use App\Models\NetworkLink;
use App\Models\NetworkNode;
use Illuminate\Support\Collection;

class GisMapService
{
    /** @return array{nodes: list<array<string, mixed>>, cables: list<array<string, mixed>>, links: list<array<string, mixed>>, legend: array<string, mixed>, stats: array<string, int>} */
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
            'legend' => $this->legendConfig(),
            'stats' => [
                'nodes' => $nodes->count(),
                'cables' => $this->loadCables($nodeIds)->count(),
                'links' => $this->loadLinks($nodeIds)->count(),
            ],
        ];
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
                'splitter',
                'odp.customerConnections.splitterPort.splitter',
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
                'sourcePort:id,port_number,label,direction',
                'targetPort:id,port_number,label,direction',
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
            'weight' => $this->cableLineWeight($cable),
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
            $portInfo = $this->odpPortSummary($node);

            return trim("{$typeLabel} {$code} {$name}".($portInfo ? " - {$portInfo}" : ''));
        }

        if ($node->type === NetworkNodeType::Splitter && $node->splitter) {
            $ratio = $node->splitter->ratio?->value ?? '';

            return trim("{$typeLabel} {$code} {$name}".($ratio ? " ({$ratio})" : ''));
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
                ? "Port {$node->odp->port_used}/{$node->odp->port_capacity}"
                : null,
            NetworkNodeType::Odc => $node->odc
                ? "Kapasitas {$node->odc->port_used}/{$node->odc->port_capacity}"
                : null,
            NetworkNodeType::Customer => $node->customer?->service_type,
            default => null,
        };
    }

    private function odpPortSummary(NetworkNode $node): ?string
    {
        $connection = $node->odp?->customerConnections->first();

        if (! $connection) {
            return null;
        }

        $splitter = $connection->splitterPort?->splitter;
        $ratio = $splitter?->ratio?->value;
        $spLabel = $ratio ? 'SP'.explode(':', $ratio)[1] : null;
        $port = $connection->odp_port_number ?? $connection->splitterPort?->port_number;

        if ($spLabel && $port) {
            return "{$spLabel} / Port {$port}";
        }

        if ($port) {
            return "Port {$port}";
        }

        return $spLabel;
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
            'splitter' => 'splitter',
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
            'splitter' => '#6A1B9A',
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

        $portLabel = $link->sourcePort?->label ?? $link->targetPort?->label;

        return match ($linkType) {
            NetworkLinkType::Drop => 'Drop'.($portLabel ? " · {$portLabel}" : ''),
            NetworkLinkType::PatchCord => 'Patch'.($portLabel ? " · {$portLabel}" : ''),
            NetworkLinkType::SplitterConnection => 'Splitter'.($portLabel ? " · {$portLabel}" : ''),
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
            'splitter_connection' => '#8E24AA',
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
            'splitter_connection' => 2,
            'patch_cord' => 2,
            default => 2,
        };
    }

    /** @return list<int>|null */
    private function linkDashPattern(string $linkType): ?array
    {
        return match ($linkType) {
            'patch_cord' => [6, 4],
            'splitter_connection' => [4, 4],
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
                ['key' => 'splitter_connection', 'label' => 'Splitter', 'color' => '#8E24AA'],
                ['key' => 'patch_cord', 'label' => 'Patch Cord', 'color' => '#F9A825'],
            ],
            'node_types' => collect(NetworkNodeType::cases())->map(fn (NetworkNodeType $type) => [
                'key' => $type->value,
                'label' => $type->label(),
                'color' => $this->nodeColor($type->value),
            ])->values()->all(),
        ];
    }
}
