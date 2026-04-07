<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCodificationController extends Controller
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected AuditService $auditService
    ) {}

    /**
     * Liste des documents en attente de codification.
     */
    public function index()
    {
        $documents = Document::with(['creator'])
            ->where('status', 'pending_codification')
            ->where('current_role', 'admin')
            ->latest()
            ->paginate(20);

        return view('admin.codification.index', compact('documents'));
    }

    /**
     * L'admin attribue un code au document et le renvoie au créateur.
     */
    public function codify(Request $request, Document $document)
    {
        if ($document->status !== 'pending_codification') {
            return back()->withErrors(['error' => 'Ce document n\'est pas en attente de codification.']);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:documents,code,' . $document->id],
        ]);

        $this->workflowService->codify($document, Auth::user(), $data['code']);

        $this->auditService->log(Auth::id(), 'document_codified', $document, ['code' => $data['code']], $request);

        return redirect()->route('admin.documents.codification')
            ->with('status', 'Document codifié "' . $data['code'] . '" et renvoyé au créateur.');
    }
}
