<?php

namespace Tests\Feature;

use App\Enums\NetworkNodeType;
use App\Models\FiberCable;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Otb;
use App\Models\User;
use App\Services\Network\CableProvisioningService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TubeColorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CablePlannedActivationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, TubeColorSeeder::class]);

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('super-admin');
    }

    public function test_new_cable_defaults_to_planned(): void
    {
        $start = $this->makeNode(NetworkNodeType::Otb, 'OTB-PLN');
        $end = $this->makeNode(NetworkNodeType::Odc, 'ODC-PLN', $start->id);

        $response = $this->actingAs($this->user)->post(route('cables.store'), [
            'code' => 'CBL-PLN-NEW',
            'name' => 'Planned Cable',
            'cable_type' => 'distribution',
            'core_count' => 12,
            'start_node_id' => $start->id,
            'end_node_id' => $end->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('fiber_cables', [
            'code' => 'CBL-PLN-NEW',
            'status' => 'planned',
        ]);
    }

    public function test_can_create_cable_without_end_node(): void
    {
        $start = $this->makeNode(NetworkNodeType::Odc, 'ODC-OPEN');

        $response = $this->actingAs($this->user)->post(route('cables.store'), [
            'code' => 'CBL-OPEN-END',
            'name' => 'Open Ended Cable',
            'cable_type' => 'distribution',
            'core_count' => 12,
            'start_node_id' => $start->id,
            'end_node_id' => '',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('fiber_cables', [
            'code' => 'CBL-OPEN-END',
            'start_node_id' => $start->id,
            'end_node_id' => null,
            'status' => 'planned',
        ]);
    }

    public function test_can_clear_end_node_on_cable_update(): void
    {
        $start = $this->makeNode(NetworkNodeType::Odc, 'ODC-CLR');
        $end = $this->makeNode(NetworkNodeType::Odp, 'ODP-CLR', $start->id);
        $cable = $this->makeCable('CBL-CLR', $start->id, $end->id, 'planned');

        $response = $this->actingAs($this->user)->put(route('cables.update', $cable), [
            'code' => $cable->code,
            'name' => $cable->name,
            'cable_type' => 'distribution',
            'core_count' => 12,
            'start_node_id' => $start->id,
            'end_node_id' => '',
            'status' => 'planned',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('fiber_cables', [
            'id' => $cable->id,
            'end_node_id' => null,
        ]);
    }

    public function test_can_activate_planned_cable(): void
    {
        $start = $this->makeNode(NetworkNodeType::Otb, 'OTB-ACT');
        $end = $this->makeNode(NetworkNodeType::Odc, 'ODC-ACT', $start->id);
        $cable = $this->makeCable('CBL-ACT', $start->id, $end->id, 'planned');

        $response = $this->actingAs($this->user)
            ->post(route('cables.activate', $cable));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fiber_cables', [
            'id' => $cable->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('network_links', [
            'cable_id' => $cable->id,
            'source_node_id' => $start->id,
            'target_node_id' => $end->id,
            'status' => 'active',
        ]);
    }

    public function test_joint_with_explicit_cores_activates_planned_cables(): void
    {
        $otbNode = $this->makeNode(NetworkNodeType::Otb, 'OTB-JNT');
        $odcNode = $this->makeNode(NetworkNodeType::Odc, 'ODC-JNT', $otbNode->id);

        Otb::query()->create([
            'network_node_id' => $otbNode->id,
            'code' => 'OTB-JNT',
            'olt_id' => null,
        ]);

        Odc::query()->create([
            'network_node_id' => $odcNode->id,
            'code' => 'ODC-JNT',
            'otb_id' => Otb::query()->where('network_node_id', $otbNode->id)->value('id'),
            'port_capacity' => 8,
            'port_used' => 0,
        ]);

        $cable = $this->makeCable('CBL-JNT', $otbNode->id, $odcNode->id, 'planned');
        $coreA = $cable->cores()->where('core_number', 2)->first();
        $coreB = $cable->cores()->where('core_number', 2)->first();

        $response = $this->actingAs($this->user)->post(route('core-joints.store'), [
            'joint_type' => 'otb_odc',
            'source_node_id' => $otbNode->id,
            'target_node_id' => $odcNode->id,
            'fiber_core_a_id' => $coreA->id,
            'fiber_core_b_id' => $coreB->id,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('core-joints.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fiber_cables', ['id' => $cable->id, 'status' => 'active']);
        $this->assertDatabaseHas('core_joints', [
            'source_node_id' => $otbNode->id,
            'target_node_id' => $odcNode->id,
            'fiber_core_a_id' => $coreA->id,
            'fiber_core_b_id' => $coreB->id,
        ]);
    }

    public function test_odc_distribution_activates_planned_outbound_cable(): void
    {
        $otbNode = $this->makeNode(NetworkNodeType::Otb, 'OTB-DIST');
        $odcNode = $this->makeNode(NetworkNodeType::Odc, 'ODC-DIST', $otbNode->id);
        $odpNode = $this->makeNode(NetworkNodeType::Odp, 'ODP-DIST', $odcNode->id);

        $otb = Otb::query()->create([
            'network_node_id' => $otbNode->id,
            'code' => 'OTB-DIST',
            'olt_id' => null,
        ]);

        $odc = Odc::query()->create([
            'network_node_id' => $odcNode->id,
            'code' => 'ODC-DIST',
            'otb_id' => $otb->id,
            'port_capacity' => 8,
            'port_used' => 0,
        ]);

        Odp::query()->create([
            'network_node_id' => $odpNode->id,
            'code' => 'ODP-DIST',
            'odc_id' => $odc->id,
            'port_capacity' => 8,
            'port_used' => 0,
        ]);

        $inbound = $this->makeCable('CBL-IN-DIST', $otbNode->id, $odcNode->id, 'planned');
        $outbound = $this->makeCable('CBL-OUT-DIST', $odcNode->id, $odpNode->id, 'planned');

        $response = $this->actingAs($this->user)->put(route('network.odc.distribution.update', $odc->id), [
            'splitters' => [
                [
                    'input_cable_id' => $inbound->id,
                    'input_core_number' => 1,
                    'split_ratio' => '1:4',
                    'outputs' => [
                        [
                            'output_port' => 1,
                            'target_type' => 'odp',
                            'target_node_id' => $odpNode->id,
                            'route_mode' => 'direct',
                            'outbound_cable_id' => $outbound->id,
                            'outbound_core_number' => 3,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fiber_cables', ['id' => $outbound->id, 'status' => 'active']);
    }

    public function test_odc_edit_shows_planned_cables_panel(): void
    {
        $otbNode = $this->makeNode(NetworkNodeType::Otb, 'OTB-PNL');
        $odcNode = $this->makeNode(NetworkNodeType::Odc, 'ODC-PNL', $otbNode->id);

        $otb = Otb::query()->create([
            'network_node_id' => $otbNode->id,
            'code' => 'OTB-PNL',
            'olt_id' => null,
        ]);

        $odc = Odc::query()->create([
            'network_node_id' => $odcNode->id,
            'code' => 'ODC-PNL',
            'otb_id' => $otb->id,
            'port_capacity' => 8,
            'port_used' => 0,
        ]);

        $this->makeCable('CBL-PNL', $otbNode->id, $odcNode->id, 'planned');

        $response = $this->actingAs($this->user)
            ->get(route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $odc->id]));

        $response->assertOk();
        $response->assertSee('Kabel Planned di Node Ini', false);
        $response->assertSee('CBL-PNL', false);
        $response->assertSee('Aktifkan', false);
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

    private function makeCable(string $code, int $startNodeId, int $endNodeId, string $status = 'active'): FiberCable
    {
        $cable = FiberCable::query()->create([
            'code' => $code,
            'name' => $code,
            'cable_type' => 'distribution',
            'core_count' => 12,
            'tube_count' => 1,
            'start_node_id' => $startNodeId,
            'end_node_id' => $endNodeId,
            'status' => $status,
        ]);

        app(CableProvisioningService::class)->provision($cable);

        return $cable->fresh();
    }
}
