<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_non_super_admin_cannot_assign_super_admin_role(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        $manager->givePermissionTo('users.manage');

        $response = $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Escalated User',
            'email' => 'escalated@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super-admin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    }

    public function test_non_super_admin_can_assign_allowed_role(): void
    {
        $manager = User::factory()->create(['email_verified_at' => now()]);
        $manager->assignRole('manager');
        $manager->givePermissionTo('users.manage');

        $response = $this->actingAs($manager)->post(route('users.store'), [
            'name' => 'Tech User',
            'email' => 'tech@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teknisi',
        ]);

        $response->assertSessionDoesntHaveErrors('role');
        $this->assertDatabaseHas('users', ['email' => 'tech@example.com']);
    }

    public function test_super_admin_can_assign_super_admin_role(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Second Admin',
            'email' => 'admin2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'super-admin',
        ]);

        $response->assertSessionDoesntHaveErrors('role');
        $this->assertDatabaseHas('users', ['email' => 'admin2@example.com']);

        $created = User::query()->where('email', 'admin2@example.com')->first();
        $this->assertTrue($created->hasRole('super-admin'));
    }
}
