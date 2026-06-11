<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_network_view_can_see_noc_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('noc');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('hfnms.dashboard_title'), false);
        $response->assertSee(__('hfnms.network_health'), false);
        $response->assertSee(__('hfnms.open_tickets'), false);
        $response->assertSee(__('hfnms.upcoming_maintenance'), false);
    }

    public function test_user_without_network_view_is_forbidden(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
