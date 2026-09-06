<?php

namespace Tests\Feature\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
        // Self-service reset stays disabled — only admin-initiated setup
        // links (welcome email) mint tokens. See routes/auth.php.
        $this->markTestSkipped('Forgot-password routes are disabled in this application');
    }

    public function test_reset_password_link_can_be_requested()
    {
        $this->markTestSkipped('Forgot-password routes are disabled in this application');
    }

    public function test_reset_password_screen_can_be_rendered()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk();
    }

    public function test_password_can_be_set_with_valid_token()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'neues-passwort-123',
            'password_confirmation' => 'neues-passwort-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('neues-passwort-123', $user->fresh()->password));
    }

    public function test_password_cannot_be_set_with_invalid_token()
    {
        $user = User::factory()->create(['password' => 'original-passwort']);

        $this->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'neues-passwort-123',
            'password_confirmation' => 'neues-passwort-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('original-passwort', $user->fresh()->password));
    }
}
