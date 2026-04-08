<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use App\Services\DocumentService;
use App\Services\SignatureService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiWorkflowController extends BaseApiController
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected DocumentService $documentService,
        protected SignatureService $signatureService
    ) {}

    public function submit(Document $document, Request $request): JsonResponse
    {
        if ($request->user()->role !== 'creator' || $document->created_by !== $request->user()->id) {
            return $this->error('Non autorisé', 403);
        }

        if ($document->status !== 'draft') {
            return $this->error('Statut invalide pour soumission', 400);
        }

        $this->workflowService->submit($document, $request->user());

        return $this->success(null, 'Document soumis au vérificateur');
    }

    public function validate(Document $document, Request $request): JsonResponse
    {
        if ($request->user()->role !== 'validator' || $document->current_role !== 'validator') {
            return $this->error('Non autorisé', 403);
        }

        if (! $this->documentService->verifyIntegrity($document)) {
            return $this->error('Échec d\'intégrité : Le fichier a été modifié illicitement.', 422);
        }

        $this->workflowService->validate($document, $request->user());

        return $this->success(null, 'Document validé par le vérificateur');
    }

    public function reject(Document $document, Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
            'deadline' => 'required|date',
        ]);

        if (! in_array($request->user()->role, ['validator', 'approver'])) {
            return $this->error('Non autorisé', 403);
        }

        $this->workflowService->reject($document, $request->user(), $request->input('message'), $request->input('deadline'));

        return $this->success(null, 'Document rejeté et renvoyé au créateur');
    }

    public function approve(Document $document, Request $request): JsonResponse
    {
        if ($request->user()->role !== 'approver' || $document->current_role !== 'approver') {
            return $this->error('Non autorisé', 403);
        }

        if (! $this->documentService->verifyIntegrity($document)) {
            return $this->error('Échec d\'intégrité : Le fichier a été modifié illicitement.', 422);
        }

        $this->workflowService->validate($document, $request->user());

        return $this->success(null, 'Document validé par l\'approbateur');
    }

    public function sign(Document $document, Request $request): JsonResponse
    {
        if (! $this->signatureService->canSign($document, $request->user())) {
            return $this->error('Signature non autorisee pour ce document', 403);
        }

        if (! $this->documentService->verifyIntegrity($document)) {
            return $this->error('Echec d integrite du fichier', 422);
        }

        $this->signatureService->sign($document, $request->user());

        return $this->success($document->fresh(), 'Document signe');
    }
}
