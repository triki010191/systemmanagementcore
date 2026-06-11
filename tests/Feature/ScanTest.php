<?php

namespace Tests\Feature;

use App\Enums\NetworkNodeType;
use App\Models\FiberCable;
use App\Models\NetworkNode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_scan_network_node_by_uuid(): void
    {
        $node = $this->makeNode(NetworkNodeType::Odp, 'ODP-SCAN');

        $response = $this->get(route('scan.show', $node->uuid));

        $response->assertOk();
        $response->assertSee('ODP-SCAN', false);
    }

    public function test_guest_can_scan_cable_by_code(): void
    {
        $start = $this->makeNode(NetworkNodeType::Otb, 'OTB-SCAN');
        $end = $this->makeNode(NetworkNodeType::Odc, 'ODC-SCAN', $start->id);

        FiberCable::query()->create([
            'code' => 'CBL-SCAN',
            'name' => 'Scan Cable',
            'cable_type' => 'distribution',
            'core_count' => 12,
            'tube_count' => 1,
            'start_node_id' => $start->id,
            'end_node_id' => $end->id,
            'status' => 'active',
        ]);

        $response = $this->get(route('scan.cable', 'CBL-SCAN'));

        $response->assertOk();
        $response->assertSee('CBL-SCAN', false);
    }

    public function test_unknown_uuid_returns_404(): void
    {
        $response = $this->get(route('scan.show', (string) Str::uuid()));

        $response->assertNotFound();
    }

    private function makeNode(NetworkNodeType $type, string $code, ?int $parentId = null): NetworkNode
    {
        return NetworkNode::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => $type,
            'code' => $code,
            'name' => $code,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'parent_id' => $parentId,
            'status' => 'active',
        ]);
    }
}
