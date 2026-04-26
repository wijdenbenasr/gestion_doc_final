<?php

namespace App\Http\Controllers;

use App\DTOs\DocumentData;
use App\Models\Document;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use App\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DocumentRepositoryInterface $documentRepository,
        protected DocumentService $documentService
    ) {}

    /** Redirige vers l'interface propre au rôle */
    public function dashboard(): RedirectResponse
    {
        return match (Auth::user()->role) {
            'creator' => redirect()->route('documents.creator.index'),
            'validator' => redirect()->route('workflow.validator.index'),
            'approver' => redirect()->route('workflow.approver.index'),
            'admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('login'),
        };
    }

    public function indexCreator(Request $request): View
    {
        $status = $request->query('status');
        $documents = $this->documentRepository->getByRolePaginated('creator', Auth::id(), $status);

        $stats = Document::query()
            ->where('created_by', Auth::id())
            ->selectRaw("
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'pending_codification' THEN 1 ELSE 0 END) as pending_codification,
                SUM(CASE WHEN status = 'in_validation' THEN 1 ELSE 0 END) as in_validation,
                SUM(CASE WHEN status = 'ready_for_pdf' THEN 1 ELSE 0 END) as ready_for_pdf,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'finalized' THEN 1 ELSE 0 END) as finalized
            ")
            ->first();

        return view('documents.creator-index', compact('documents', 'stats', 'status'));
    }
    public function myDocuments(Request $request): View
    {
        $status = $request->query('status');
        $query = Document::with(['creator'])->where('created_by', Auth::id())->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $documents = $query->paginate(20);

        $stats = Document::query()
            ->where('created_by', Auth::id())
            ->selectRaw("
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN status = 'pending_codification' THEN 1 ELSE 0 END) as pending_codification,
                SUM(CASE WHEN status = 'in_validation' THEN 1 ELSE 0 END) as in_validation,
                SUM(CASE WHEN status = 'ready_for_pdf' THEN 1 ELSE 0 END) as ready_for_pdf,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'finalized' THEN 1 ELSE 0 END) as finalized
            ")
            ->first();

        return view('documents.my-documents', compact('documents', 'stats', 'status'));
    }
    public function archive(Request $request): View
    {
        $query = Document::with(['creator'])
            ->where('status', 'finalized')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('code', 'like', '%' . $search . '%')
                  ->orWhere('ligne', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('aio')) {
            $query->where('aio', $request->aio);
        }
        if ($request->filled('ligne')) {
            $query->where('ligne', 'like', '%' . $request->ligne . '%');
        }
        if ($request->filled('phase')) {
            $query->where('phase', $request->phase);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('updated_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('updated_at', '<=', $request->date_to);
        }

        $documents = $query->paginate(20);

        return view('documents.archive', [
            'documents' => $documents,
            'types' => Document::TYPES,
            'aios' => Document::AIOS,
            'filters' => $request->only(['search', 'type', 'aio', 'ligne', 'phase', 'date_from', 'date_to'])
        ]);
    }

    public function create(): View
    {
        $isAdmin = auth()->user()->role === 'admin';
        $canEditLigne = true;
        $backRoute = $isAdmin ? route('admin.dashboard') : route('documents.creator.index');

        return view('documents.create', [
            'types' => Document::TYPES,
            'aios' => Document::AIOS,
            'document' => null,
            'canEditLigne' => $canEditLigne,
            'backRoute' => $backRoute,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $isAdmin = $request->user()->role === 'admin';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(Document::TYPES))],
            'aio' => ['required', 'in:'.implode(',', array_keys(Document::AIOS))],
            'ligne' => ['required', 'string', 'max:255'],
            'phase' => ['required', 'in:serie,projet'],
            'nom_phase' => ['nullable', 'required_if:phase,projet', 'string', 'max:255'],
            'nom_serie' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'file' => ['required', 'file', 'mimes:docx,xlsx,pdf', 'max:20480'],
        ], [
            'nom_phase.required_if' => 'Le nom de la phase est obligatoire lorsque le type est "Projet".',
            'type.in' => 'Type de document invalide.',
            'aio.in' => 'AIO invalide.',
            'file.mimes' => 'Format accepte : .docx, .xlsx, .pdf (max 20 Mo).',
        ]);

        $ligne = $isAdmin ? $data['ligne'] : ($data['ligne'] ?: 'Ligne 1');

        $document = $this->documentService->createDocument(
            new DocumentData(
                name: $data['name'],
                type: $data['type'],
                aio: $data['aio'],
                ligne: $ligne,
                phase: $data['phase'],
                nom_phase: $data['nom_phase'] ?? null,
                nom_serie: $data['nom_serie'] ?? null,
                deadline: $data['deadline'] ?? null,
            ),
            $request->file('file'),
            $request->user()
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document cree.', 'data' => $document], 201);
        }

        $redirectRoute = $isAdmin ? 'admin.dashboard' : 'documents.creator.index';

        return redirect()->route($redirectRoute)
            ->with('status', 'Document "'.$document->name.'" cree avec succes.');
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        $isAdmin = auth()->user()->role === 'admin';
        $canEditLigne = $isAdmin;
        $backRoute = $isAdmin ? route('admin.dashboard') : route('documents.creator.index');

        return view('documents.create', [
            'types' => Document::TYPES,
            'aios' => Document::AIOS,
            'document' => $document,
            'canEditLigne' => $canEditLigne,
            'backRoute' => $backRoute,
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $document);

        $isAdmin = $request->user()->role === 'admin';

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.implode(',', array_keys(Document::TYPES))],
            'aio' => ['required', 'in:'.implode(',', array_keys(Document::AIOS))],
            'ligne' => ['required', 'string', 'max:255'],
            'phase' => ['required', 'in:serie,projet'],
            'nom_phase' => ['nullable', 'required_if:phase,projet', 'string', 'max:255'],
            'nom_serie' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'mimes:docx,xlsx,pdf', 'max:20480'],
        ], [
            'nom_phase.required_if' => 'Le nom de la phase est obligatoire lorsque le type est "Projet".',
        ]);

        $ligne = $isAdmin ? $data['ligne'] : ($document->ligne ?: $data['ligne']);

        $updated = $this->documentService->updateDocument(
            $document,
            new DocumentData(
                name: $data['name'],
                type: $data['type'],
                aio: $data['aio'],
                ligne: $ligne,
                phase: $data['phase'],
                nom_phase: $data['nom_phase'] ?? null,
                nom_serie: $data['nom_serie'] ?? null,
                deadline: $data['deadline'] ?? null,
            ),
            $request->file('file')
        );

        $redirectRoute = $isAdmin ? 'admin.dashboard' : 'documents.creator.index';

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document mis à jour.', 'data' => $updated]);
        }

        return redirect()->route($redirectRoute)
            ->with('status', 'Document mis à jour.');
    }

    public function requestDeletion(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('requestDeletion', $document);
        $this->documentRepository->delete($document);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document supprimé.']);
        }

        return redirect()->route('documents.creator.index')
            ->with('status', 'Document supprimé.');
    }

    public function destroyAdmin(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('forceDelete', $document);
        $this->documentRepository->delete($document);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document supprimé.']);
        }

        return redirect()->route('admin.dashboard')
            ->with('status', 'Document supprimé.');
    }

    public function convertToPdf($id)
    {
        $document = Document::findOrFail($id);

        if ($document->status !== 'ready_for_pdf') {
            abort(403, 'Document non autorisé');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.pdf_template', compact('document'));

        $document->pdf_converti = true;
        $document->save();

        return $pdf->download($document->code . '_' . str_replace(' ', '_', $document->nom) . '.pdf');
    }
}
