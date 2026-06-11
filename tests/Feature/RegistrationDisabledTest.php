<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_returns_404_when_disabled(): void
    {
        config(['hfnms.allow_registration' => false]);

        $response = $this->get(route('register'));

        $response->assertNotFound();
    }

    public function test_registration_post_is_rejected_when_disabled(): void
    {
        config(['hfnms.allow_registration' => false]);

        $response = $this->post(route('register'), [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }
}
