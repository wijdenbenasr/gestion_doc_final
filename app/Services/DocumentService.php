<?php

namespace App\Services;

use App\DTOs\DocumentData;
use App\Models\Document;
use App\Models\User;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(
        protected DocumentRepositoryInterface $documentRepository
    ) {}

    public function createDocument(DocumentData $data, UploadedFile $file, User $creator): Document
    {
        $path = $file->store('documents', 'private');
        $hash = hash('sha256', Storage::disk('private')->get($path));

        $payload = array_merge($data->toArray(), [
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'created_by' => $creator->id,
            'current_owner_id' => $creator->id,
            'version' => 1,
            'revision' => 'v1.0',
            'revision_major' => 1,
            'revision_minor' => 0,
            'status' => 'draft',
            'current_role' => 'creator',
            'hash' => $hash,
        ]);

        return $this->documentRepository->create($payload);
    }

    public function createDocumentWithCode(DocumentData $data, UploadedFile $file, User $admin, string $code, int $createurId): Document
    {
        $path = $file->store('documents', 'private');
        $hash = hash('sha256', Storage::disk('private')->get($path));

        $payload = array_merge($data->toArray(), [
            'code' => $code,
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'created_by' => $admin->id,
            'current_owner_id' => $createurId,
            'version' => 1,
            'revision' => 'v1.0',
            'revision_major' => 1,
            'revision_minor' => 0,
            'status' => 'EN_MODIFICATION',
            'current_role' => 'creator',
            'hash' => $hash,
        ]);

        $document = $this->documentRepository->create($payload);

        // Create transmission record: admin -> createur
        $document->transmissions()->create([
            'sent_by' => $admin->id,
            'to_role' => 'creator',
            'action' => 'submit',
            'status' => 'pending',
        ]);

        // Send notification to createur
        $createur = User::find($createurId);
        if ($createur) {
            $createur->notify(new \App\Notifications\DocumentAssignedNotification($document));
        }

        return $document;
    }

    public function updateDocument(Document $document, DocumentData $data, ?UploadedFile $file = null): Document
    {
        $payload = $data->toArray();
        $oldFilePath = $document->file_path;

        if ($file) {
            $path = $file->store('documents', 'private');
            $hash = hash('sha256', Storage::disk('private')->get($path));

            $payload['file_path'] = $path;
            $payload['file_original_name'] = $file->getClientOriginalName();
            $payload['hash'] = $hash;

            // Increment revision_minor ONLY when file is uploaded during EN_MODIFICATION
            if ($document->status === 'EN_MODIFICATION') {
                $payload['revision_minor'] = ($document->revision_minor ?? 0) + 1;
                $major = $document->revision_major ?? 1;
                $minor = $payload['revision_minor'];
                $payload['revision'] = "v{$major}.{$minor}";
            }
        }

        $this->documentRepository->update($document, $payload);

        if ($file && $oldFilePath && Storage::disk('private')->exists($oldFilePath)) {
            Storage::disk('private')->delete($oldFilePath);
        }

        return $document->refresh();
    }

    public function verifyIntegrity(Document $document): bool
    {
        if (! $document->file_path || ! Storage::disk('private')->exists($document->file_path)) {
            return false;
        }

        $currentHash = hash('sha256', Storage::disk('private')->get($document->file_path));

        return hash_equals($document->hash ?? '', $currentHash);
    }
}
