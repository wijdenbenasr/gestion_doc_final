<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_create_and_submit_document()
    {
        Storage::fake('private');

        $creator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('test.docx', 100);

        $response = $this->actingAs($creator)->postJson('/documents', [
            'name' => 'Doc Test',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L1',
            'phase' => 'serie',
            'file' => $file,
        ]);

        $response->assertCreated(); // Changé de assertRedirect() à assertCreated()
        $this->assertDatabaseHas('documents', ['name' => 'Doc Test', 'status' => 'draft']);

        $document = Document::first();

        $response = $this->actingAs($creator)->postJson("/workflow/creator/{$document->id}/send"); // Changé de post() à postJson()

        $response->assertOk(); // Changé de assertRedirect() à assertOk()
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'status' => 'pending_codification', 'current_role' => 'admin']);
    }

    public function test_creator_signs_and_sends_coded_document_to_validator()
    {
        Storage::fake('private');

        $creator = User::factory()->create([
            'role' => 'creator',
            'is_admin_approved' => true,
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('coded.docx', 100);
        $path = $file->store('documents', 'private');
        $hash = hash('sha256', Storage::disk('private')->get($path));

        $document = Document::create([
            'name' => 'Doc Code',
            'code' => 'QMS-SOP-AIO1-001',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L1',
            'phase' => 'serie',
            'file_path' => $path,
            'file_original_name' => 'coded.docx',
            'created_by' => $creator->id,
            'current_owner_id' => $creator->id,
            'status' => 'draft',
            'current_role' => 'creator',
            'hash' => $hash,
            'revision' => '1.0',
        ]);

        $response = $this->actingAs($creator)->postJson("/workflow/creator/{$document->id}/send-to-validator");

        $response->assertOk();
        $this->assertDatabaseHas('document_signatures', ['document_id' => $document->id, 'role' => 'creator']);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'status' => 'in_validation', 'current_role' => 'validator']);
    }

    public function test_validator_can_validate_document()
    {
        Storage::fake('private');
        $creator = User::factory()->create(['role' => 'creator', 'is_admin_approved' => true, 'email_verified_at' => now()]);
        $validator = User::factory()->create(['role' => 'validator', 'is_admin_approved' => true, 'email_verified_at' => now()]);

        $file = UploadedFile::fake()->create('test.docx', 100);
        $path = $file->store('documents', 'private');
        $hash = hash('sha256', Storage::disk('private')->get($path));

        $document = Document::create([
            'name' => 'Doc Validator',
            'type' => 'sop',
            'aio' => 'aio1',
            'ligne' => 'L1',
            'phase' => 'serie',
            'file_path' => $path,
            'file_original_name' => 'test.docx',
            'created_by' => $creator->id,
            'status' => 'in_validation',
            'current_role' => 'validator',
            'hash' => $hash,
            'revision' => '1.0',
        ]);

        $response = $this->actingAs($validator)->postJson("/workflow/validator/{$document->id}/validate");

        $response->assertOk();
        $this->assertDatabaseHas('document_signatures', ['document_id' => $document->id, 'role' => 'validator']);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'current_role' => 'approver']);
    }

    public function test_non_admin_cannot_access_mobile()
    {
        $creator = User::factory()->create(['role' => 'creator', 'is_admin_approved' => true, 'email_verified_at' => now()]);

        $response = $this->actingAs($creator)->get('/', [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
        ]);

        $response->assertStatus(403);
    }
}
