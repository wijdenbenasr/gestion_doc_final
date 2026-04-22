<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DailyPendingWorkflowAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DailyOpenWorkflowAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_open_alerts_are_sent_to_matching_actors(): void
    {
        Notification::fake();

        $creator = $this->activeUser('creator');
        $idleCreator = $this->activeUser('creator');
        $validator = $this->activeUser('validator');
        $approver = $this->activeUser('approver');
        $admin = $this->activeUser('admin');

        $this->makeDocument([
            'name' => 'Doc creator rejected',
            'code' => 'QMS-SOP-101',
            'created_by' => $creator->id,
            'current_owner_id' => $creator->id,
            'status' => 'rejected',
            'current_role' => 'creator',
            'deadline' => now()->subDay(),
        ]);

        $this->makeDocument([
            'name' => 'Doc creator coded draft',
            'code' => 'QMS-SOP-102',
            'created_by' => $creator->id,
            'current_owner_id' => $creator->id,
            'status' => 'draft',
            'current_role' => 'creator',
            'deadline' => now()->addDay(),
        ]);

        $this->makeDocument([
            'name' => 'Plain draft without coding',
            'code' => null,
            'created_by' => $idleCreator->id,
            'current_owner_id' => $idleCreator->id,
            'status' => 'draft',
            'current_role' => 'creator',
        ]);

        $this->makeDocument([
            'name' => 'Doc validator pending',
            'code' => 'QMS-SOP-201',
            'created_by' => $creator->id,
            'status' => 'in_validation',
            'current_role' => 'validator',
            'deadline' => now()->addHours(12),
        ]);

        $this->makeDocument([
            'name' => 'Doc approver pending',
            'code' => 'QMS-SOP-301',
            'created_by' => $creator->id,
            'status' => 'in_validation',
            'current_role' => 'approver',
            'deadline' => now()->addDays(3),
        ]);

        $this->makeDocument([
            'name' => 'Doc admin codification',
            'code' => null,
            'created_by' => $creator->id,
            'status' => 'pending_codification',
            'current_role' => 'admin',
        ]);

        $this->makeDocument([
            'name' => 'Doc admin final',
            'code' => 'QMS-SOP-401',
            'created_by' => $creator->id,
            'status' => 'in_validation',
            'current_role' => 'admin',
        ]);

        $this->artisan('documents:send-daily-open-alerts')
            ->expectsOutput('Alertes quotidiennes envoyees a 4 acteur(s) pour 6 document(s) en attente.')
            ->assertSuccessful();

        Notification::assertSentTo(
            $creator,
            DailyPendingWorkflowAlert::class,
            fn (DailyPendingWorkflowAlert $notification) => $notification->recipientRole === 'creator'
                && $notification->pendingCount === 2
                && $notification->overdueCount === 1
                && $notification->dueSoonCount === 1
        );

        Notification::assertNotSentTo($idleCreator, DailyPendingWorkflowAlert::class);

        Notification::assertSentTo(
            $validator,
            DailyPendingWorkflowAlert::class,
            fn (DailyPendingWorkflowAlert $notification) => $notification->recipientRole === 'validator'
                && $notification->pendingCount === 1
                && $notification->dueSoonCount === 1
        );

        Notification::assertSentTo(
            $approver,
            DailyPendingWorkflowAlert::class,
            fn (DailyPendingWorkflowAlert $notification) => $notification->recipientRole === 'approver'
                && $notification->pendingCount === 1
        );

        Notification::assertSentTo(
            $admin,
            DailyPendingWorkflowAlert::class,
            fn (DailyPendingWorkflowAlert $notification) => $notification->recipientRole === 'admin'
                && $notification->pendingCount === 2
                && ($notification->breakdown['codification'] ?? 0) === 1
                && ($notification->breakdown['traitement final'] ?? 0) === 1
        );
    }

    protected function activeUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);
    }

    protected function makeDocument(array $overrides = []): Document
    {
        return Document::create(array_merge([
            'name' => 'Doc test',
            'code' => 'QMS-SOP-000',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L1',
            'phase' => 'serie',
            'nom_serie' => 'S1',
            'file_path' => 'documents/doc-test.docx',
            'file_original_name' => 'doc-test.docx',
            'created_by' => User::factory()->create([
                'role' => 'creator',
                'is_admin_approved' => true,
                'email_verified_at' => now(),
            ])->id,
            'current_owner_id' => null,
            'version' => 1,
            'revision' => '1.0',
            'status' => 'draft',
            'current_role' => 'creator',
        ], $overrides));
    }
}
