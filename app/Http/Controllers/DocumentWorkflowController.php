<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\DocumentSignature;
use App\Models\AuditLog;
use App\Models\Transmission;
use App\Notifications\DocumentTaskNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentWorkflowController extends Controller
{
    public function creatorSendToAdmin(Request $request, $id): RedirectResponse|JsonResponse
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || $document->created_by !== Auth::id()) {
            abort(403);
        }

        if (!in_array($document->status, ['draft'])) {
            return redirect()->back()->with('error', 'Le document doit être en brouillon pour être envoyé à l\'admin.');
        }

        $document->status = 'pending_codification';
        $document->current_role = 'admin';
        $document->save();

        LogTransmission($document, Auth::user(), 'creator', 'admin', 'submit');

        User::where('role', 'admin')
            ->where('is_admin_approved', true)
            ->each(function($admin) use ($document) {
                $admin->notify(new DocumentTaskNotification(
                    $document,
                    'Un nouveau document attend votre codification.',
                    'pending_codification'
                ));
            });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'creator_sent_to_admin',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document envoyé à l\'admin pour codification.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document envoyé à l\'admin pour codification.');
    }

    public function adminAssignCode(Request $request, $id): RedirectResponse|JsonResponse
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'code' => 'required|string|unique:documents,code,'.$id,
        ]);

        $document = Document::findOrFail($id);
        $document->code = $request->code;
        $document->status = 'draft';
        $document->current_role = 'creator';
        $document->save();

        LogTransmission($document, Auth::user(), 'admin', 'creator', 'codify', "Code attribué : {$request->code}");

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été codifié. Vous pouvez maintenant le soumettre au validateur.',
                'draft'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'admin_assigned_code',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['code' => $request->code],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Code attribué et document renvoyé au créateur.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Code attribué et document renvoyé au créateur.');
    }

    public function creatorSendToValidator(Request $request, $id): RedirectResponse|JsonResponse
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || $document->created_by !== Auth::id()) {
            abort(403);
        }

        if (!in_array($document->status, ['draft', 'rejected']) || empty($document->code)) {
            return redirect()->back()->with('error', 'Le document doit être codifié avant d\'être soumis au validateur.');
        }

        $document->status = 'in_validation';
        $document->current_role = 'validator';
        $document->save();

        LogTransmission($document, Auth::user(), 'creator', 'validator', 'submit_to_validator');

        User::where('role', 'validator')
            ->where('is_admin_approved', true)
            ->each(function($validator) use ($document) {
                $validator->notify(new DocumentTaskNotification(
                    $document,
                    'Un document codifié attend votre validation.',
                    'in_validation'
                ));
            });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'creator_sent_to_validator',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document soumis au validateur.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document soumis au validateur.');
    }

    public function validatorValidate(Request $request, $id): RedirectResponse|JsonResponse
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'validator') {
            abort(403);
        }

        if (!in_array($document->status, ['in_validation']) || $document->current_role !== 'validator') {
            return redirect()->back()->with('error', 'Ce document n\'est pas en phase de validation.');
        }

        $document->status = 'approbation';
        $document->current_role = 'approver';
        $document->version = ($document->version ?? 1) + 1;
        $document->revision = (float) ($document->revision ?? 1.0) + 0.1;
        $document->validated_by = Auth::id();
        $document->validated_at = now();
        $document->save();

DocumentSignature::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'role' => 'approver',
            'order' => 3,
            'signed_at' => now(),
        ]);

        LogTransmission($document, Auth::user(), 'approver', 'admin', 'approve');

        $admins = User::where('role', 'admin')
            ->where('is_admin_approved', true)
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new DocumentTaskNotification(
                $document,
                'Un document a ete approuve. Il attend votre validation finale.',
                'validation_admin'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'approver_approved',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->input('commentaire', '')],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document approuvé et envoyé à l\'admin pour validation finale.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document approuvé et envoyé à l\'admin pour validation finale.');
    }

    public function approverReject(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'commentaire' => 'required|string',
            'deadline_correction' => 'required|date',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'approver') {
            abort(403);
        }

        if ($document->status !== 'approbation' || $document->current_role !== 'approver') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $document->status = 'rejected';
        $document->current_role = 'creator';
        $document->commentaire_rejet = $request->commentaire;
        $document->deadline_correction = $request->deadline_correction;
        $document->save();

        LogTransmission($document, Auth::user(), 'approver', 'creator', 'reject', $request->commentaire);

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été rejeté par l\'approbateur : ' . $request->commentaire,
                'rejected'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'approver_rejected',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->commentaire, 'deadline' => $request->deadline_correction],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document rejeté et créateur notifié.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document rejeté et créateur notifié.');
    }

    public function adminValidate(Request $request, $id): RedirectResponse|JsonResponse
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($document->status !== 'validation_admin' || $document->current_role !== 'admin') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $document->status = 'ready_for_pdf';
        $document->current_role = 'creator';
        $document->version = ($document->version ?? 1) + 1;
        $document->revision = (float) ($document->revision ?? 1.0) + 0.1;
        $document->admin_validated_by = Auth::id();
        $document->admin_validated_at = now();
        $document->save();

        DocumentSignature::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'role' => 'admin',
            'order' => 4,
            'signed_at' => now(),
        ]);

        LogTransmission($document, Auth::user(), 'admin', 'creator', 'validate_for_pdf');

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été validé par l\'admin. Veuillez le convertir en PDF et le signer.',
                'ready_for_pdf'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'admin_validated',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->input('commentaire', '')],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document validé. Créateur notifié pour conversion PDF.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document validé. Créateur notifié pour conversion PDF.');
    }

    public function convertToPdf($id)
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || $document->created_by !== Auth::id()) {
            abort(403);
        }

        if ($document->status !== 'ready_for_pdf') {
            return redirect()->back()->with('error', 'Le document doit être prêt pour PDF.');
        }

        $document->pdf_converti = true;
        $document->status = 'pdf_converti';
        $document->save();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'document_converted_to_pdf',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        $pdf = Pdf::loadView('documents.pdf_template', compact('document'));

        return $pdf->download($document->code . '_' . str_replace(' ', '_', $document->name) . '.pdf');
    }

    public function creatorSign(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'document_signe' => 'required|file|mimes:pdf|max:20480',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || $document->created_by !== Auth::id()) {
            abort(403);
        }

        if (!in_array($document->status, ['ready_for_pdf', 'pdf_converti'])) {
            return redirect()->back()->with('error', 'Le document doit être prêt pour signature.');
        }

        $path = $request->file('document_signe')->store('documents/signes', 'public');

        $document->pdf_signe_createur = $path;
        $document->status = 'signing_validator';
        $document->current_role = 'validator';
        $document->save();

        DocumentSignature::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'role' => 'creator',
            'order' => 1,
            'signed_at' => now(),
        ]);

        LogTransmission($document, Auth::user(), 'creator', 'validator', 'sign');

$validators = User::where('role', 'validator')
            ->where('is_admin_approved', true)
            ->get();

        foreach ($validators as $validator) {
            $validator->notify(new DocumentTaskNotification(
                $document,
                'Le createur a signe le document. Veuillez le telecharger, signer et renvoyer.',
                'signing_validator'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'creator_signed',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document signé et envoyé au validateur.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document signé et envoyé au validateur.');
    }

    public function validatorSign(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'document_signe' => 'required|file|mimes:pdf|max:20480',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'validator') {
            abort(403);
        }

        if ($document->status !== 'signing_validator' || $document->current_role !== 'validator') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $path = $request->file('document_signe')->store('documents/signes', 'public');

        $document->pdf_signe_validateur = $path;
        $document->status = 'signing_approver';
        $document->current_role = 'approver';
        $document->save();

        DocumentSignature::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'role' => 'validator',
            'order' => 2,
            'signed_at' => now(),
        ]);

        LogTransmission($document, Auth::user(), 'validator', 'approver', 'sign');

        User::where('role', 'approver')
            ->where('is_admin_approved', true)
            ->each(function($approver) use ($document) {
                $approver->notify(new DocumentTaskNotification(
                    $document,
                    'Le validateur a signé le document. Veuillez le télécharger, signer et renvoyer.',
                    'signing_approver'
                ));
            });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'validator_signed',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document signé et envoyé à l\'approbateur.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document signé et envoyé à l\'approbateur.');
    }

    public function approverSign(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'document_signe' => 'required|file|mimes:pdf|max:20480',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'approver') {
            abort(403);
        }

        if ($document->status !== 'signing_approver' || $document->current_role !== 'approver') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $path = $request->file('document_signe')->store('documents/signes', 'public');

        $document->pdf_signe_approbateur = $path;
        $document->status = 'signing_admin';
        $document->current_role = 'admin';
        $document->save();

        DocumentSignature::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'role' => 'approver',
            'order' => 3,
            'signed_at' => now(),
        ]);

        LogTransmission($document, Auth::user(), 'approver', 'admin', 'sign');

        User::where('role', 'admin')
            ->where('is_admin_approved', true)
            ->each(function($admin) use ($document) {
                $admin->notify(new DocumentTaskNotification(
                    $document,
                    'L\'approbateur a signé le document. Veuillez effectuer la signature finale.',
                    'signing_admin'
                ));
            });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'approver_signed',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document signé et envoyé à l\'admin pour signature finale.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document signé et envoyé à l\'admin pour signature finale.');
    }

    public function adminSign(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'document_signe' => 'required|file|mimes:pdf|max:20480',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        if ($document->status !== 'signing_admin' || $document->current_role !== 'admin') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $path = $request->file('document_signe')->store('documents/signes/final', 'public');

        $document->pdf_signe_final = $path;
        $document->status = 'finalized';
        $document->current_role = null;
        $document->archived_at = now();
        $document->save();

        DocumentSignature::updateOrCreate([
            'document_id' => $document->id,
            'role' => 'admin',
        ], [
            'user_id' => Auth::id(),
            'order' => 4,
            'signed_at' => now(),
        ]);

        LogTransmission($document, Auth::user(), 'admin', 'archive', 'final_sign');

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été signé par tous et est maintenant archivé.',
                'finalized'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'admin_signed_final',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document signé et archivé avec succès !',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document signé et archivé avec succès !');
    }

    public function validatorIndex(Request $request): View
    {
        return $this->reviewerDashboard('validator', 'documents.validator-index', $request->query('filter'));
    }

    public function approverIndex(Request $request): View
    {
        return $this->reviewerDashboard('approver', 'documents.approver-index', $request->query('filter'));
    }

    public function adminReject(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'commentaire' => 'required|string',
            'deadline_correction' => 'nullable|date',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        if (!in_array($document->status, ['validation_admin', 'signing_admin']) || $document->current_role !== 'admin') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $document->status = 'rejected';
        $document->current_role = 'creator';
        $document->commentaire_rejet = $request->commentaire;
        $document->deadline_correction = $request->deadline_correction;
        $document->save();

        LogTransmission($document, Auth::user(), 'admin', 'creator', 'reject', $request->commentaire);

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été rejeté par l\'admin : ' . $request->commentaire,
                'rejected'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'admin_rejected',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->commentaire, 'deadline' => $request->deadline_correction],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document rejeté et créateur notifié.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document rejeté et créateur notifié.');
    }

    private function reviewerDashboard(string $role, string $view, ?string $filter = null): View
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->limit(6)->get();

        $baseQuery = Document::with(['creator']);

        if ($filter === 'pending') {
            $documents = (clone $baseQuery)
                ->whereIn('status', ['in_validation', 'approbation'])
                ->where('current_role', $role)
                ->latest()
                ->paginate(20);
        } elseif ($filter === 'processed') {
            $documents = (clone $baseQuery)
                ->whereHas('signatures', function ($query) use ($user, $role) {
                    $query->where('user_id', $user->id)->where('role', $role);
                })
                ->latest('updated_at')
                ->paginate(20);
        } elseif ($filter === 'rejected') {
            $documents = (clone $baseQuery)
                ->whereHas('transmissions', function ($query) use ($user) {
                    $query->where('sent_by', $user->id)->where('action', 'reject');
                })
                ->latest()
                ->paginate(20);
        } else {
            $documents = (clone $baseQuery)
                ->whereIn('status', ['in_validation', 'approbation'])
                ->where('current_role', $role)
                ->latest()
                ->paginate(20);
        }

        $alertes = null;
        $documentsAValider = null;

        if (!$filter || $filter === 'pending') {
            $signingStatus = $role === 'validator' ? 'signing_validator' : 'signing_approver';

            $alertes = Document::where('status', $signingStatus)
                ->where('current_role', $role)
                ->whereNotNull('deadline')
                ->orderByRaw('CASE
                    WHEN deadline < NOW() THEN 0
                    WHEN deadline < DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1
                    ELSE 2
                END')
                ->limit(5)
                ->get();

            $validationStatus = $role === 'approver' ? 'approbation' : 'in_validation';
            $documentsAValider = Document::where('status', $validationStatus)
                ->where('current_role', $role)
                ->whereNotNull('deadline')
                ->orderByRaw('CASE
                    WHEN deadline < NOW() THEN 0
                    WHEN deadline < DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1
                    ELSE 2
                END')
                ->limit(10)
                ->get();
        }

        $processedQuery = Document::with(['creator'])
            ->whereHas('signatures', function ($query) use ($user, $role) {
                $query->where('user_id', $user->id)->where('role', $role);
            });

        $processedDocuments = (clone $processedQuery)->latest('updated_at')->limit(6)->get();

        $processedCount = (clone $processedQuery)->count();

        if ($role === 'validator') {
            $processedCount += Document::where('validated_by', $user->id)->count();
        } elseif ($role === 'approver') {
            $processedCount += Document::where('approved_by', $user->id)->count();
        }

        $stats = [
            'pending' => Document::where('status', $role === 'approver' ? 'approbation' : 'in_validation')
                ->where('current_role', $role)->count(),
            'processed' => $processedCount,
            'rejected' => Document::whereHas('transmissions', function ($query) use ($user) {
                $query->where('sent_by', $user->id)->where('action', 'reject');
            })->count(),
            'notifications' => $user->unreadNotifications()->count(),
        ];

        return view($view, compact('documents', 'processedDocuments', 'stats', 'filter', 'notifications', 'alertes', 'documentsAValider'));
    }
}

function LogTransmission(Document $document, $user, string $from, string $to, string $action, ?string $comment = null): void
{
    Transmission::create([
        'document_id' => $document->id,
        'from_role' => $from,
        'to_role' => $to,
        'action' => $action,
        'status' => 'done',
        'comment' => $comment,
        'sent_by' => $user->id,
    ]);
}
