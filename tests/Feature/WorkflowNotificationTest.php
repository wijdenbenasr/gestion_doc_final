<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentTaskNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_send_to_admin_notifies_admins(): void
    {
        Notification::fake();
        Storage::fake('private');

        $creator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('draft.docx', 100);

        $this->actingAs($creator)->post('/documents', [
            'name' => 'Doc Notification',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L1',
            'phase' => 'serie',
            'file' => $file,
        ]);

        $document = Document::firstOrFail();

        $this->actingAs($creator)
            ->post(route('workflow.creator.send', $document))
            ->assertRedirect();

        Notification::assertSentTo(
            $admin,
            DocumentTaskNotification::class,
            fn (DocumentTaskNotification $notification) => $notification->document->is($document)
                && str_contains($notification->messageText, 'codification')
        );
    }

    public function test_creator_send_to_validator_notifies_validators(): void
    {
        Notification::fake();

        $creator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $validator = User::factory()->create([
            'role' => 'validator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $document = Document::create([
            'name' => 'Doc pour validation',
            'code' => 'QMS-SOP-001',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L1',
            'phase' => 'serie',
            'nom_serie' => 'S1',
            'file_path' => 'documents/doc-pour-validation.docx',
            'file_original_name' => 'doc-pour-validation.docx',
            'created_by' => $creator->id,
            'current_owner_id' => $creator->id,
            'version' => 1,
            'revision' => '1.0',
            'status' => 'draft',
            'current_role' => 'creator',
        ]);

        $this->actingAs($creator)
            ->post(route('workflow.creator.send_to_validator', $document))
            ->assertRedirect();

        Notification::assertSentTo(
            $validator,
            DocumentTaskNotification::class,
            fn (DocumentTaskNotification $notification) => $notification->document->is($document)
                && str_contains($notification->messageText, 'validation')
        );
    }

    public function test_admin_validate_notifies_only_document_creator_for_pdf_conversion(): void
    {
        Notification::fake();

        $creator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $otherCreator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $document = Document::create([
            'name' => 'Doc PDF',
            'code' => 'QMS-SOP-002',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L2',
            'phase' => 'serie',
            'nom_serie' => 'S2',
            'file_path' => 'documents/doc-pdf.docx',
            'file_original_name' => 'doc-pdf.docx',
            'created_by' => $creator->id,
            'current_owner_id' => null,
            'version' => 1,
            'revision' => '1.0',
            'status' => 'in_validation',
            'current_role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.workflow.validate', $document))
            ->assertRedirect(route('admin.dashboard'));

        Notification::assertSentTo(
            $creator,
            DocumentTaskNotification::class,
            fn (DocumentTaskNotification $notification) => $notification->document->is($document)
                && str_contains($notification->messageText, 'PDF')
        );

        Notification::assertNotSentTo($otherCreator, DocumentTaskNotification::class);
    }

    public function test_validator_dashboard_uses_shared_header_notifications(): void
    {
        $creator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $validator = User::factory()->create([
            'role' => 'validator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $document = Document::create([
            'name' => 'Doc attente validation',
            'code' => 'QMS-SOP-010',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L10',
            'phase' => 'serie',
            'nom_serie' => 'S10',
            'file_path' => 'documents/doc-attente-validation.docx',
            'file_original_name' => 'doc-attente-validation.docx',
            'created_by' => $creator->id,
            'current_owner_id' => null,
            'version' => 1,
            'revision' => '1.0',
            'status' => 'in_validation',
            'current_role' => 'validator',
        ]);

        $this->actingAs($validator)
            ->get(route('workflow.validator.index'))
            ->assertOk()
            ->assertSee('Validation requise : '.$document->name, false)
            ->assertDontSee('Vos dernieres alertes documentaires.');
    }

    public function test_approver_dashboard_uses_shared_header_notifications(): void
    {
        $creator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $approver = User::factory()->create([
            'role' => 'approver',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $document = Document::create([
            'name' => 'Doc attente approbation',
            'code' => 'QMS-SOP-011',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L11',
            'phase' => 'serie',
            'nom_serie' => 'S11',
            'file_path' => 'documents/doc-attente-approbation.docx',
            'file_original_name' => 'doc-attente-approbation.docx',
            'created_by' => $creator->id,
            'current_owner_id' => null,
            'version' => 1,
            'revision' => '1.0',
            'status' => 'in_validation',
            'current_role' => 'approver',
        ]);

        $this->actingAs($approver)
            ->get(route('workflow.approver.index'))
            ->assertOk()
            ->assertSee('Approbation requise : '.$document->name, false)
            ->assertDontSee('Vos dernieres alertes documentaires.');
    }
}
