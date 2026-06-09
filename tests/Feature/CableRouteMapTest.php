<?php

namespace Tests\Feature;

use App\Models\FiberCable;
use App\Models\NetworkNode;
use App\Models\User;
use App\Services\Network\CableProvisioningService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TubeColorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CableRouteMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_cable_edit_form_includes_route_map(): void
    {
        $this->seed([RolePermissionSeeder::class, TubeColorSeeder::class]);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('super-admin');

        $start = NetworkNode::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => 'otb',
            'code' => 'OTB-MAP',
            'name' => 'OTB Map',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'status' => 'active',
        ]);

        $end = NetworkNode::query()->create([
            'uuid' => (string) Str::uuid(),
            'type' => 'odc',
            'code' => 'ODC-MAP',
            'name' => 'ODC Map',
            'latitude' => -6.21,
            'longitude' => 106.81,
            'status' => 'active',
        ]);

        $cable = FiberCable::query()->create([
            'code' => 'CBL-MAP',
            'name' => 'Cable Map Test',
            'cable_type' => 'distribution',
            'core_count' => 12,
            'tube_count' => 1,
            'start_node_id' => $start->id,
            'end_node_id' => $end->id,
            'route_geometry' => [
                ['lat' => -6.2, 'lng' => 106.8],
                ['lat' => -6.205, 'lng' => 106.805],
                ['lat' => -6.21, 'lng' => 106.81],
            ],
            'status' => 'active',
        ]);

        app(CableProvisioningService::class)->provision($cable);

        $response = $this->actingAs($user)->get(route('cables.edit', $cable));

        $response->assertOk();
        $response->assertSee('cable-route-map', false);
        $response->assertSee('Jalur Kabel di Peta', false);
        $response->assertSee('-6.205', false);
    }
}
