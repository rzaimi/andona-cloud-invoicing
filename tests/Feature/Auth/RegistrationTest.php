<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_registration_screen_can_be_rendered()
    {
        // Registration routes are disabled in this application
        $this->markTestSkipped('Registration routes are disabled in this application');
    }

    public function test_new_users_can_register()
    {
        // Registration routes are disabled in this application
        $this->markTestSkipped('Registration routes are disabled in this application');
    }
}
