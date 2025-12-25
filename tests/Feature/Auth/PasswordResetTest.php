<?php

namespace Tests\Feature\Auth;

use App\Modules\User\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_reset_password_link_screen_can_be_rendered()
    {
        // Password reset is disabled - routes are commented out
        $this->markTestSkipped('Password reset routes are disabled in this application');
    }

    public function test_reset_password_link_can_be_requested()
    {
        // Password reset is disabled - routes are commented out
        $this->markTestSkipped('Password reset routes are disabled in this application');
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        // Password reset is disabled - routes are commented out
        $this->markTestSkipped('Password reset routes are disabled in this application');
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        // Password reset is disabled - routes are commented out
        $this->markTestSkipped('Password reset routes are disabled in this application');
    }
}
