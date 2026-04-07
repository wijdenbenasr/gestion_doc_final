<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_list_update_resend_and_delete_users(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Created',
                'prenom' => 'User',
                'email' => 'created@example.com',
                'cin' => 'CIN-050',
                'matricule' => 'MAT-050',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'creator',
                'is_admin_approved' => '1',
            ])
            ->assertRedirect();

        $createdUser = User::where('email', 'created@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'role' => 'creator',
            'is_admin_approved' => true,
        ]);

        Notification::assertSentTo($createdUser, EmailVerificationCodeNotification::class);

        $pendingUser = User::factory()->create([
            'role' => null,
            'is_admin_approved' => false,
            'email_verified_at' => null,
            'admin_approved_at' => null,
            'email' => 'pending@example.com',
            'cin' => 'CIN-100',
            'matricule' => 'MAT-100',
        ]);

        $deletableUser = User::factory()->create([
            'role' => 'creator',
            'email' => 'delete@example.com',
            'cin' => 'CIN-200',
            'matricule' => 'MAT-200',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('created@example.com')
            ->assertSee('pending@example.com')
            ->assertSee('delete@example.com');

        Notification::fake();

        $this->actingAs($admin)
            ->post(route('admin.users.resend_code', $createdUser))
            ->assertRedirect();

        Notification::assertSentTo($createdUser->fresh(), EmailVerificationCodeNotification::class);

        Notification::fake();

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $pendingUser), [
                'name' => 'Pending',
                'prenom' => 'User',
                'email' => 'pending@example.com',
                'cin' => 'CIN-100',
                'matricule' => 'MAT-100',
                'role' => 'checker',
                'is_admin_approved' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $pendingUser->id,
            'role' => 'checker',
            'is_admin_approved' => true,
        ]);

        Notification::assertSentTo($pendingUser->fresh(), EmailVerificationCodeNotification::class);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $deletableUser))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $deletableUser->id]);
    }
}
