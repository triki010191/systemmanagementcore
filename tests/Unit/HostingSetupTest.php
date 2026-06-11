<?php

namespace Tests\Unit;

use App\Support\HostingSetup;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HostingSetupTest extends TestCase
{
    #[Test]
    public function it_rejects_invalid_setup_token(): void
    {
        $this->assertFalse(HostingSetup::validateToken(null));
        $this->assertFalse(HostingSetup::validateToken('wrong-key'));
    }

    #[Test]
    public function it_accepts_valid_setup_token_from_env(): void
    {
        $this->assertTrue(HostingSetup::validateToken('test-hosting-setup-key'));
    }

    #[Test]
    public function it_validates_cron_token(): void
    {
        $this->assertTrue(HostingSetup::validateCronToken('test-cron-secret-key'));
        $this->assertFalse(HostingSetup::validateCronToken('invalid'));
    }

    #[Test]
    public function it_generates_long_random_secret(): void
    {
        $secret = HostingSetup::generateSecret();

        $this->assertSame(64, strlen($secret));
        $this->assertNotSame(HostingSetup::generateSecret(), $secret);
    }
}
