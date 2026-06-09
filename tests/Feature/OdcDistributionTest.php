<?php

namespace Tests\Feature;

use App\Enums\NetworkNodeType;
use App\Models\FiberCable;
use App\Models\NetworkNode;
use App\Models\Odc;
use App\Models\OdcSplitter;
use App\Models\Odp;
use App\Models\Otb;
use App\Models\User;
use App\Services\Network\CableProvisioningService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TubeColorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OdcDistributionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Odc $odc;

    private NetworkNode $odpNode;

    private FiberCable $inboundCable;

    private FiberCable $outboundCable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            TubeColorSeeder::class,
        ]);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('super-admin');

        $otbNode = $this->makeNode(NetworkNodeType::Otb, 'OTB-TEST');
        $odcNode = $this->makeNode(NetworkNodeType::Odc, 'ODC-TEST', $otbNode->id);
        $this->odpNode = $this->makeNode(NetworkNodeType::Odp, 'ODP-TEST', $odcNode->id);

        $otb = Otb::query()->create([
            'network_node_id' => $otbNode->id,
            'code' => 'OTB-TEST',
            'olt_id' => null,
        ]);

        $this->odc = Odc::query()->create([
            'network_node_id' => $odcNode->id,
            'code' => 'ODC-TEST',
            'otb_id' => $otb->id,
            'port_capacity' => 8,
            'port_used' => 0,
        ]);

        Odp::query()->create([
            'network_node_id' => $this->odpNode->id,
            'code' => 'ODP-TEST',
            'odc_id' => $this->odc->id,
            'port_capacity' => 8,
            'port_used' => 0,
        ]);

        $this->inboundCable = $this->makeCable('CBL-IN', $otbNode->id, $odcNode->id);
        $this->outboundCable = $this->makeCable('CBL-OUT', $odcNode->id, $this->odpNode->id);
    }

    public function test_odc_edit_includes_distribution_panel(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $this->odc->id]));

        $response->assertOk();
        $response->assertSee('Distribusi Splitter', false);
    }

    public function test_can_save_odc_splitter_distribution_to_odp(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('network.odc.distribution.update', $this->odc->id), [
                'splitters' => [
                    [
                        'label' => 'Splitter Utama',
                        'input_cable_id' => $this->inboundCable->id,
                        'input_core_number' => 1,
                        'split_ratio' => '1:4',
                        'outputs' => [
                            [
                                'output_port' => 1,
                                'target_type' => 'odp',
                                'target_node_id' => $this->odpNode->id,
                                'outbound_cable_id' => $this->outboundCable->id,
                                'outbound_core_number' => 2,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $this->odc->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('odc_splitters', [
            'odc_id' => $this->odc->id,
            'input_cable_id' => $this->inboundCable->id,
            'input_core_number' => 1,
            'split_ratio' => '1:4',
        ]);

        $splitter = OdcSplitter::query()->where('odc_id', $this->odc->id)->first();
        $this->assertNotNull($splitter);

        $this->assertDatabaseHas('odc_splitter_outputs', [
            'odc_splitter_id' => $splitter->id,
            'output_port' => 1,
            'target_node_id' => $this->odpNode->id,
            'outbound_cable_id' => $this->outboundCable->id,
            'outbound_core_number' => 2,
        ]);

        $this->odc->refresh();
        $this->assertSame(1, $this->odc->cores_to_odp);

        $editResponse = $this->actingAs($this->user)
            ->get(route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $this->odc->id]));

        $editResponse->assertOk();
        $editResponse->assertSee('CBL-OUT', false);
        $editResponse->assertSee('ODP-TEST', false);
        $editResponse->assertSee('Kabel Keluar dari ODC', false);
    }

    public function test_distribution_options_returns_cable_cores(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('network.odc.distribution.options', [
                'odc' => $this->odc->id,
                'cable_id' => $this->outboundCable->id,
            ]));

        $response->assertOk();
        $response->assertJsonStructure(['cores']);
        $response->assertJsonFragment(['core_number' => 2]);
    }

    public function test_can_save_odc_splitter_distribution_via_joint_to_odp(): void
    {
        $jointNode = $this->makeNode(NetworkNodeType::Customer, 'JNT-TEST', $this->odpNode->id);
        $odcToJointCable = $this->makeCable('CBL-ODC-JNT', $this->odc->network_node_id, $jointNode->id);
        $jointToOdpCable = $this->makeCable('CBL-JNT-ODP', $jointNode->id, $this->odpNode->id);

        $response = $this->actingAs($this->user)
            ->put(route('network.odc.distribution.update', $this->odc->id), [
                'splitters' => [
                    [
                        'input_cable_id' => $this->inboundCable->id,
                        'input_core_number' => 1,
                        'split_ratio' => '1:4',
                        'outputs' => [
                            [
                                'output_port' => 1,
                                'target_type' => 'odp',
                                'target_node_id' => $this->odpNode->id,
                                'route_mode' => 'via_joint',
                                'outbound_cable_id' => $odcToJointCable->id,
                                'outbound_core_number' => 4,
                                'downstream_cable_id' => $jointToOdpCable->id,
                                'downstream_core_number' => 4,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $this->odc->id]));
        $response->assertSessionHas('success');

        $splitter = OdcSplitter::query()->where('odc_id', $this->odc->id)->first();
        $this->assertNotNull($splitter);

        $this->assertDatabaseHas('odc_splitter_outputs', [
            'odc_splitter_id' => $splitter->id,
            'output_port' => 1,
            'route_mode' => 'via_joint',
            'outbound_cable_id' => $odcToJointCable->id,
            'outbound_core_number' => 4,
            'downstream_cable_id' => $jointToOdpCable->id,
            'downstream_core_number' => 4,
        ]);

        $this->assertDatabaseHas('core_joints', [
            'source_node_id' => $this->odc->network_node_id,
            'target_node_id' => $this->odpNode->id,
            'joint_type' => 'odc_odp',
            'core_number' => 4,
        ]);
    }

    public function test_can_save_multiple_splitters_with_different_output_ports(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('network.odc.distribution.update', $this->odc->id), [
                'splitters' => [
                    [
                        'label' => 'Splitter 1',
                        'input_cable_id' => $this->inboundCable->id,
                        'input_core_number' => 1,
                        'split_ratio' => '1:4',
                        'outputs' => [
                            [
                                'output_port' => 1,
                                'target_type' => 'odp',
                                'target_node_id' => $this->odpNode->id,
                                'outbound_cable_id' => $this->outboundCable->id,
                                'outbound_core_number' => 1,
                            ],
                        ],
                    ],
                    [
                        'label' => 'Splitter 2',
                        'input_cable_id' => $this->inboundCable->id,
                        'input_core_number' => 2,
                        'split_ratio' => '1:4',
                        'outputs' => [
                            [
                                'output_port' => 2,
                                'target_type' => 'odp',
                                'target_node_id' => $this->odpNode->id,
                                'outbound_cable_id' => $this->outboundCable->id,
                                'outbound_core_number' => 2,
                            ],
                            [
                                'output_port' => 3,
                                'target_type' => 'odp',
                                'target_node_id' => $this->odpNode->id,
                                'outbound_cable_id' => $this->outboundCable->id,
                                'outbound_core_number' => 3,
                            ],
                            [
                                'output_port' => 4,
                                'target_type' => 'odp',
                                'target_node_id' => $this->odpNode->id,
                                'outbound_cable_id' => $this->outboundCable->id,
                                'outbound_core_number' => 4,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('odc_splitters', 2);

        $secondSplitter = OdcSplitter::query()
            ->where('odc_id', $this->odc->id)
            ->where('label', 'Splitter 2')
            ->first();

        $this->assertNotNull($secondSplitter);

        $this->assertDatabaseHas('odc_splitter_outputs', [
            'odc_splitter_id' => $secondSplitter->id,
            'output_port' => 2,
            'outbound_core_number' => 2,
        ]);

        $this->assertDatabaseHas('odc_splitter_outputs', [
            'odc_splitter_id' => $secondSplitter->id,
            'output_port' => 4,
            'outbound_core_number' => 4,
        ]);
    }

    public function test_odc_edit_form_uses_unique_alpine_keys_per_splitter_and_port(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $this->odc->id]));

        $response->assertOk();
        $response->assertSee('output.availableOutboundCables', false);
        $response->assertSee('refreshCableOptions(output)', false);
        $response->assertSee('x-init="init()"', false);
        $response->assertSee(__('hfnms.select_core'), false);
    }

    public function test_odc_edit_form_includes_core_datalist_for_outbound_cable(): void
    {
        $this->actingAs($this->user)
            ->put(route('network.odc.distribution.update', $this->odc->id), [
                'splitters' => [
                    [
                        'input_cable_id' => $this->inboundCable->id,
                        'input_core_number' => 1,
                        'split_ratio' => '1:4',
                        'outputs' => [
                            [
                                'output_port' => 1,
                                'target_type' => 'odp',
                                'target_node_id' => $this->odpNode->id,
                                'outbound_cable_id' => $this->outboundCable->id,
                                'outbound_core_number' => 3,
                            ],
                        ],
                    ],
                ],
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('network.assets.edit', [NetworkNodeType::Odc->routeSlug(), $this->odc->id]));

        $response->assertOk();
        $response->assertSee('coreCache', false);
        $response->assertSee('outbound_core_number', false);
        $response->assertSee('refreshAllOutputOptions(splitter)', false);
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

    private function makeCable(string $code, int $startNodeId, int $endNodeId): FiberCable
    {
        $cable = FiberCable::query()->create([
            'code' => $code,
            'name' => $code,
            'cable_type' => 'distribution',
            'core_count' => 12,
            'tube_count' => 1,
            'start_node_id' => $startNodeId,
            'end_node_id' => $endNodeId,
            'status' => 'active',
        ]);

        app(CableProvisioningService::class)->provision($cable);

        return $cable->fresh();
    }
}
