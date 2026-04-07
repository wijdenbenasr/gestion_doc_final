<?php

namespace App\Http\Controllers\Api;

use App\DTOs\DocumentData;
use App\Models\Document;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApiDocumentController extends BaseApiController
{
    public function __construct(
        protected DocumentRepositoryInterface $documentRepository,
        protected DocumentService $documentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentRepository->getByRolePaginated(
            Auth::user()->role,
            Auth::id()
        );

        return $this->success($documents);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $baseQuery = Document::query();

        if ($user->role === 'creator') {
            $baseQuery->where('created_by', $user->id);
        } elseif (in_array($user->role, ['checker', 'approver'], true)) {
            $baseQuery->where('current_role', $user->role);
        }

        return $this->success([
            'total' => (clone $baseQuery)->count(),
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'pending_codification' => (clone $baseQuery)->where('status', 'pending_codification')->count(),
            'in_validation' => (clone $baseQuery)->where('status', 'in_validation')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'finalized' => (clone $baseQuery)->where('status', 'finalized')->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'type'      => 'required|in:' . implode(',', array_keys(Document::TYPES)),
            'aio'       => 'required|in:' . implode(',', array_keys(Document::AIOS)),
            'ligne'     => 'required|string|max:255',
            'phase'     => 'required|in:serie,projet',
            'nom_phase' => 'nullable|required_if:phase,projet|string|max:255',
            'nom_serie' => 'nullable|string|max:255',
            'deadline'  => 'nullable|date',
            'file'      => 'required|file|mimes:docx,xlsx,pdf|max:20480',
        ]);

        $document = $this->documentService->createDocument(
            DocumentData::fromArray($data),
            $request->file('file'),
            $request->user()
        );

        return $this->success($document, 'Document cree', 201);
    }

    public function show(Document $document): JsonResponse
    {
        $document->load(['creator', 'signatures.user', 'versions', 'auditLogs.user']);

        return $this->success($document);
    }

    public function update(Document $document, Request $request): JsonResponse
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:' . implode(',', array_keys(Document::TYPES)),
            'aio' => 'required|in:' . implode(',', array_keys(Document::AIOS)),
            'ligne' => 'required|string|max:255',
            'phase' => 'required|in:serie,projet',
            'nom_phase' => 'nullable|required_if:phase,projet|string|max:255',
            'nom_serie' => 'nullable|string|max:255',
            'deadline' => 'nullable|date',
            'file' => 'nullable|file|mimes:docx,xlsx,pdf|max:20480',
        ]);

        $updated = $this->documentService->updateDocument(
            $document,
            DocumentData::fromArray($data),
            $request->file('file')
        );

        return $this->success($updated, 'Document mis a jour');
    }

    public function download(Document $document)
    {
        $this->authorize('download', $document);

        if (! $document->file_path || ! Storage::disk('private')->exists($document->file_path)) {
            return $this->error('Fichier introuvable', 404);
        }

        $filename = $document->file_original_name
            ?: ($document->name . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION));

        return Storage::disk('private')->download($document->file_path, $filename);
    }

    public function timeline(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $document->load(['transmissions.sender', 'signatures.user', 'auditLogs', 'versions.creator']);

        return $this->success([
            'transmissions' => $document->transmissions,
            'signatures' => $document->signatures,
            'audit_logs' => $document->auditLogs,
            'versions' => $document->versions,
        ]);
    }
}
