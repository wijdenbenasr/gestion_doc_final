<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DocumentWorkflowController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected DocumentService $documentService,
        protected AuditService $auditService
    ) {}

    public function creatorSendToAdmin(Document $document, Request $request): RedirectResponse|JsonResponse
    {
        $this->checkOwner($document);

        if (! in_array($document->status, ['draft'], true)) {
            abort(400, 'Le document doit etre en brouillon pour etre envoye a l admin.');
        }

        $this->workflowService->submit($document, $request->user());
        $this->auditService->log(Auth::id(), 'submitted_to_admin', $document, [], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document envoye a l administrateur pour codification.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->route('documents.creator.index')
            ->with('status', 'Document envoye a l administrateur pour codification.');
    }

    public function creatorSendToValidator(Document $document, Request $request): RedirectResponse|JsonResponse
    {
        $this->checkOwner($document);

        if ($document->status !== 'draft' || empty($document->code)) {
            abort(400, 'Le document doit etre codifie avant d etre soumis au validateur.');
        }

        $this->workflowService->submitToValidator($document, $request->user());
        $this->auditService->log(Auth::id(), 'creator_signed_and_submitted_to_validator', $document, [], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document signe par le createur et soumis au validateur.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->route('documents.creator.index')
            ->with('status', 'Document signe puis soumis au validateur.');
    }

    public function validatorIndex(): View
    {
        return $this->reviewerDashboard('validator', 'documents.validator-index');
    }

    public function validatorValidate(Document $document, Request $request): RedirectResponse|JsonResponse
    {
        $this->checkRole('validator');

        if ($document->status !== 'in_validation' || $document->current_role !== 'validator') {
            abort(400);
        }

        if (! $this->documentService->verifyIntegrity($document)) {
            return $this->integrityError($request);
        }

        $this->workflowService->validate($document, $request->user());
        $this->auditService->log(Auth::id(), 'validator_validated', $document, [], $request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Valide par le validateur.', 'data' => $document->refresh()]);
        }

        return redirect()->route('workflow.validator.index')
            ->with('status', 'Document valide et envoye a l approbateur.');
    }

    public function validatorReject(Document $document, Request $request): RedirectResponse|JsonResponse
    {
        $this->checkRole('validator');

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'deadline' => ['required', 'date'],
        ]);

        $this->workflowService->reject($document, $request->user(), $data['message'], $data['deadline']);
        $this->auditService->log(Auth::id(), 'validator_rejected', $document, ['message' => $data['message']], $request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document rejete.', 'data' => $document->refresh()]);
        }

        return redirect()->route('workflow.validator.index')
            ->with('status', 'Document rejete et renvoye au createur.');
    }

    public function approverIndex(): View
    {
        return $this->reviewerDashboard('approver', 'documents.approver-index');
    }

    public function approverValidate(Document $document, Request $request): RedirectResponse|JsonResponse
    {
        $this->checkRole('approver');

        if (! $this->documentService->verifyIntegrity($document)) {
            return $this->integrityError($request);
        }

        $this->workflowService->validate($document, $request->user());
        $this->auditService->log(Auth::id(), 'approver_validated', $document, [], $request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document approuve.', 'data' => $document->refresh()]);
        }

        return redirect()->route('workflow.approver.index')
            ->with('status', 'Document approuve et envoye a l administrateur pour validation finale.');
    }

    public function approverReject(Document $document, Request $request): RedirectResponse|JsonResponse
    {
        $this->checkRole('approver');

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'deadline' => ['required', 'date'],
        ]);

        $this->workflowService->reject($document, $request->user(), $data['message'], $data['deadline']);
        $this->auditService->log(Auth::id(), 'approver_rejected', $document, ['message' => $data['message']], $request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document rejete.', 'data' => $document->refresh()]);
        }

        return redirect()->route('workflow.approver.index')
            ->with('status', 'Document rejete et renvoye au createur.');
    }

    public function adminSign(Document $document, Request $request): RedirectResponse|JsonResponse
    {
        $this->checkRole('admin');

        if (! $this->documentService->verifyIntegrity($document)) {
            return $this->integrityError($request);
        }

        $this->workflowService->validate($document, $request->user());
        $this->auditService->log(Auth::id(), 'admin_signed_final', $document, [], $request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document signe et finalise.', 'data' => $document->refresh()]);
        }

        return back()->with('status', 'Document signe et archive.');
    }

    private function reviewerDashboard(string $role, string $view): View
    {
        $user = Auth::user();

        $documents = Document::with(['creator'])
            ->where('status', 'in_validation')
            ->where('current_role', $role)
            ->latest()
            ->paginate(20);

        $processedQuery = Document::with(['creator'])
            ->whereHas('signatures', function ($query) use ($user, $role) {
                $query->where('user_id', $user->id)->where('role', $role);
            });

        $processedDocuments = (clone $processedQuery)
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $stats = [
            'pending' => $documents->total(),
            'processed' => (clone $processedQuery)->count(),
            'rejected' => Document::whereHas('transmissions', function ($query) use ($user) {
                $query->where('sent_by', $user->id)->where('action', 'reject');
            })->count(),
            'notifications' => $user->notifications()->whereNull('read_at')->count(),
        ];

        $notifications = $user->notifications()->latest()->limit(8)->get();

        return view($view, compact('documents', 'processedDocuments', 'stats', 'notifications'));
    }

    private function checkRole(string $role): void
    {
        if (Auth::user()->role !== $role) {
            abort(403);
        }
    }

    private function checkOwner(Document $document): void
    {
        if (Auth::user()->role !== 'creator' || $document->created_by !== Auth::id()) {
            abort(403);
        }
    }

    private function integrityError(Request $request): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Echec de verification d integrite du fichier.'], 422);
        }

        return back()->withErrors(['integrity' => 'Echec de verification d integrite du fichier.']);
    }
}
