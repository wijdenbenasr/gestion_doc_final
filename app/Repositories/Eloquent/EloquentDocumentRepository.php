<?php

namespace App\Repositories\Eloquent;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentDocumentRepository implements DocumentRepositoryInterface
{
    public function findById(int $id): ?Document
    {
        return Document::with(['creator', 'currentOwner', 'signatures.user', 'transmissions.sender'])->find($id);
    }

    public function findByCode(string $code): ?Document
    {
        return Document::where('code', $code)->first();
    }

    public function getAllPaginated(int $perPage = 20): LengthAwarePaginator
    {
        return Document::with(['creator'])->latest()->paginate($perPage);
    }

    public function getByRolePaginated(string $role, ?int $userId = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Document::with(['creator'])->latest();

        match ($role) {
            'creator'  => $query->where('created_by', $userId),
            'checker'  => $query->where('status', 'in_validation')->where('current_role', 'checker'),
            'approver' => $query->where('status', 'in_validation')->where('current_role', 'approver'),
            default    => null,
        };

        return $query->paginate($perPage);
    }

    public function create(array $data): Document
    {
        return DB::transaction(function () use ($data) {
            $document = Document::create($data);

            DocumentVersion::create([
                'document_id' => $document->id,
                'revision'    => $document->revision,
                'file_path'   => $document->file_path,
                'hash'        => $document->hash ?? '',
                'created_by'  => $document->created_by,
                'comment'     => 'Version initiale',
            ]);

            return $document;
        });
    }

    public function update(Document $document, array $data): bool
    {
        return DB::transaction(function () use ($document, $data) {
            $updated = $document->update($data);

            if ($updated && isset($data['file_path'])) {
                DocumentVersion::create([
                    'document_id' => $document->id,
                    'revision'    => $document->revision,
                    'file_path'   => $document->file_path,
                    'hash'        => $document->hash ?? '',
                    'created_by'  => auth()->id() ?? $document->created_by,
                    'comment'     => 'Mise à jour fichier',
                ]);
            }

            return $updated;
        });
    }

    public function delete(Document $document): bool
    {
        return $document->delete();
    }

    public function addVersion(Document $document, array $data): void
    {
        DocumentVersion::create([
            'document_id' => $document->id,
            'revision'    => $data['revision'],
            'file_path'   => $data['file_path'],
            'hash'        => $data['hash'],
            'created_by'  => $data['created_by'],
            'comment'     => $data['comment'] ?? null,
        ]);
    }

    public function getVersions(Document $document): Collection
    {
        return $document->versions()->latest()->get();
    }

    public function getStats(): array
    {
        return [
            'total'                => Document::count(),
            'draft'                => Document::where('status', 'draft')->count(),
            'pending_codification' => Document::where('status', 'pending_codification')->count(),
            'in_validation'        => Document::where('status', 'in_validation')->count(),
            'rejected'             => Document::where('status', 'rejected')->count(),
            'finalized'            => Document::where('status', 'finalized')->count(),
        ];
    }
}
