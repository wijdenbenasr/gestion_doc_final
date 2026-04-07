<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_approved_then_receives_code_and_can_verify_email(): void
    {
        Notification::fake();

        $password = 'secret123';

        $registerResponse = $this->post('/register', [
            'name' => 'Ben',
            'prenom' => 'Wijden',
            'cin' => 'AA123456',
            'matricule' => 'MAT123',
            'email' => 'user@example.com',
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $registerResponse->assertRedirect(route('login'));

        $user = User::where('email', 'user@example.com')->firstOrFail();

        $this->assertFalse($user->is_admin_approved);
        $this->assertNull($user->email_verified_at);
        Notification::assertNothingSent();

        $this->post('/login', [
            'email' => 'user@example.com',
            'password' => $password,
        ])->assertSessionHasErrors('email');

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $verificationCode = null;

        $this->actingAs($admin)
            ->post(route('admin.users.approve', $user), ['role' => 'creator'])
            ->assertRedirect();

        $this->post('/logout')->assertRedirect(route('login'));

        Notification::assertSentTo(
            $user->fresh(),
            EmailVerificationCodeNotification::class,
            function (EmailVerificationCodeNotification $notification) use (&$verificationCode): bool {
                $verificationCode = (string) $notification->code;

                return true;
            }
        );

        $this->assertNotNull($verificationCode);

        $this->post('/login', [
            'email' => 'user@example.com',
            'password' => $password,
        ])->assertRedirect(route('auth.verify.show', ['email' => 'user@example.com']));

        $this->post('/email/verify', [
            'email' => 'user@example.com',
            'code' => $verificationCode,
        ])->assertRedirect(route('login'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
