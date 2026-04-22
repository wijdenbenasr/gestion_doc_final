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
            'revision' => '1.0',
            'status' => 'draft',
            'current_role' => 'creator',
            'hash' => $hash,
        ]);

        return $this->documentRepository->create($payload);
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
        }

        if ($document->code && $document->status === 'draft' && $document->current_role === 'creator') {
            $payload['revision'] = $this->incrementRevision($document->revision ?? '1.0');
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

    private function incrementRevision(string $revision): string
    {
        if (! str_contains($revision, '.')) {
            return $revision.'.1';
        }

        [$major, $minor] = explode('.', $revision, 2);

        return $major.'.'.((int) $minor + 1);
    }
}
