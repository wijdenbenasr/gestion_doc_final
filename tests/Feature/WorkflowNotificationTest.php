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
}
