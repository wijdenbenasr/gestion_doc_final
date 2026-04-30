<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\DocumentSignature;
use App\Models\AuditLog;
use App\Models\Transmission;
use App\Models\DocumentVersion;
use App\Notifications\DocumentTaskNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

        if (Auth::user()->role !== 'creator' || ($document->created_by !== Auth::id() && $document->current_owner_id !== Auth::id())) {
            abort(403);
        }

        if (!in_array($document->status, ['draft', 'rejected', 'EN_MODIFICATION']) || empty($document->code)) {
            return redirect()->back()->with('error', 'Le document doit être codifié avant d\'être soumis au validateur.');
        }

        $isAfterRejection = $document->status === 'EN_MODIFICATION';
        $document->status = 'in_validation';
        $document->current_role = 'validator';
        $document->commentaire_rejet = null; // Clear rejection reason when resubmitting
        $document->save();

        // Create version record for file sent to validator
        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $document->file_path,
            'hash' => $document->hash ?? '',
            'created_by' => Auth::id(),
            'type' => 'sent_to_validator',
            'comment' => $isAfterRejection ? 'Renvoyé au validateur après modification' : 'Envoyé au validateur',
        ]);

        LogTransmission($document, Auth::user(), 'creator', 'validator', 'submit_to_validator');

        $notificationMessage = $isAfterRejection
            ? 'Le créateur a modifié et renvoyé le document après refus. Veuillez le valider.'
            : 'Un document codifié attend votre validation.';

        User::where('role', 'validator')
            ->where('is_admin_approved', true)
            ->each(function($validator) use ($document, $notificationMessage) {
                $validator->notify(new DocumentTaskNotification(
                    $document,
                    $notificationMessage,
                    'in_validation'
                ));
            });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $isAfterRejection ? 'creator_resent_to_validator_after_rejection' : 'creator_sent_to_validator',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['after_rejection' => $isAfterRejection],
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

        $document->status = 'in_approbation';
        $document->current_role = 'approver';
        $document->version = ($document->version ?? 1) + 1;
        $document->validated_by = Auth::id();
        $document->validated_at = now();
        $document->save();

        // Create version record for file sent to approver
        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $document->file_path,
            'hash' => $document->hash ?? '',
            'created_by' => Auth::id(),
            'type' => 'sent_to_approver',
            'comment' => 'Validé et envoyé à l\'approbateur',
        ]);

DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'role' => 'validator'],
            [
                'user_id' => Auth::id(),
                'order' => 2,
                'signed_at' => now(),
            ]
        );

        LogTransmission($document, Auth::user(), 'validator', 'approver', 'validate');

        User::where('role', 'approver')
            ->where('is_admin_approved', true)
            ->each(function ($approver) use ($document) {
                $approver->notify(new DocumentTaskNotification(
                    $document,
                    'Un document a été validé par le validateur. Il attend votre approbation.',
                    'approbation'
                ));
            });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'validator_validated',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->input('commentaire', '')],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document validé et envoyé à l\'approbateur.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document validé et envoyé à l\'approbateur.');
    }

    public function validatorReject(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'motif_rejet' => 'required|string|max:1000',
            'deadline_correction' => 'nullable|date',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'validator') {
            abort(403);
        }

        if (!in_array($document->status, ['in_validation']) || $document->current_role !== 'validator') {
            return redirect()->back()->with('error', 'Ce document n\'est pas en phase de validation.');
        }

        $document->status = 'EN_MODIFICATION';
        $document->current_role = 'creator';
        $document->commentaire_rejet = $request->motif_rejet;
        $document->deadline_correction = $request->deadline_correction;
        $document->save();

        LogTransmission($document, Auth::user(), 'validator', 'creator', 'reject', $request->motif_rejet);

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été refusé par le validateur. Veuillez le modifier et le renvoyer au validateur.',
                'EN_MODIFICATION'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'validator_rejected',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->motif_rejet, 'deadline' => $request->deadline_correction],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document refusé. Créateur notifié.', 'data' => $document->refresh()]);
        }

        return redirect()->back()->with('success', 'Document refusé. Le créateur a été notifié.');
    }

    public function approverApprove(Request $request, $id): RedirectResponse|JsonResponse
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'approver') {
            abort(403);
        }

        if ($document->status !== 'in_approbation' || $document->current_role !== 'approver') {
            return redirect()->back()->with('error', 'Ce document n\'est pas en phase d\'approbation.');
        }

        $document->status = 'validation_admin';
        $document->current_role = 'admin';
        $document->approved_by = Auth::id();
        $document->approved_at = now();
        $document->save();

        DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'role' => 'approver'],
            [
                'user_id' => Auth::id(),
                'order' => 3,
                'signed_at' => now(),
            ]
        );

        // Create version record for file sent to admin
        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $document->file_path,
            'hash' => $document->hash ?? '',
            'created_by' => Auth::id(),
            'type' => 'sent_to_admin',
            'comment' => 'Approuvé et envoyé à l\'admin',
        ]);

        LogTransmission($document, Auth::user(), 'approver', 'admin', 'approve');

        User::where('role', 'admin')
            ->where('is_admin_approved', true)
            ->each(function ($admin) use ($document) {
                $admin->notify(new DocumentTaskNotification(
                    $document,
                    'Un document a été approuvé. Il attend votre validation finale.',
                    'validation_admin'
                ));
            });

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'approver_approved',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->input('commentaire', '')],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Document approuvé et envoyé à l\'admin.', 'data' => $document->refresh()]);
        }

        return redirect()->back()->with('success', 'Document approuvé et envoyé à l\'admin pour validation finale.');
    }

    public function approverReject(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'motif_rejet' => 'required|string',
            'deadline_correction' => 'nullable|date',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'approver') {
            abort(403);
        }

        if ($document->status !== 'in_approbation' || $document->current_role !== 'approver') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $document->status = 'EN_MODIFICATION';
        $document->current_role = 'creator';
        $document->commentaire_rejet = $request->motif_rejet;
        $document->deadline_correction = $request->deadline_correction;
        $document->save();

        LogTransmission($document, Auth::user(), 'approver', 'creator', 'reject', $request->motif_rejet);

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été refusé par l\'approbateur. Veuillez le modifier et le renvoyer au validateur.',
                'EN_MODIFICATION'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'approver_rejected',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->motif_rejet, 'deadline' => $request->deadline_correction],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document refusé et créateur notifié.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document refusé et créateur notifié.');
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
        $document->admin_validated_by = Auth::id();
        $document->admin_validated_at = now();
        $document->save();

        DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'role' => 'approver'],
            [
                'user_id' => Auth::id(),
                'order' => 3,
                'signed_at' => now(),
            ]
        );

        // Create version record for file ready for PDF
        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $document->file_path,
            'hash' => $document->hash ?? '',
            'created_by' => Auth::id(),
            'type' => 'ready_for_pdf',
            'comment' => 'Validé par admin, prêt pour PDF',
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

    public function convertToPdf(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || ($document->created_by !== Auth::id() && $document->current_owner_id !== Auth::id())) {
            abort(403);
        }

        if ($document->status !== 'ready_for_pdf') {
            return redirect()->back()->with('error', 'Le document doit être prêt pour PDF.');
        }

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $pdfFile = $request->file('pdf_file');
        $pdfPath = 'converted_pdfs/' . $document->code . '_' . str_replace(' ', '_', $document->name) . '_' . time() . '.pdf';
        Storage::disk('public')->put($pdfPath, $pdfFile->get());

        $document->pdf_path = $pdfPath;
        $document->pdf_converti = true;
        $document->status = 'pdf_converted';
        $document->save();

        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $pdfPath,
            'hash' => hash('sha256', Storage::disk('public')->get($pdfPath)),
            'created_by' => Auth::id(),
            'type' => 'pdf_converted',
            'comment' => 'PDF converti',
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'document_converted_to_pdf',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => [],
        ]);

        return redirect()->back()->with('success', 'PDF converti avec succès.');
    }

    public function showSignForm($id): View
    {
        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || ($document->created_by !== Auth::id() && $document->current_owner_id !== Auth::id())) {
            abort(403);
        }

        if (strtolower($document->status) !== 'pdf_converted') {
            return redirect()->route('documents.my')->with('error', 'Le document doit être converti en PDF pour être signé.');
        }

        return view('documents.sign-form', compact('document'));
    }

    public function uploadSignedPdf(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'signed_pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || ($document->created_by !== Auth::id() && $document->current_owner_id !== Auth::id())) {
            abort(403);
        }

        if (strtolower($document->status) !== 'pdf_converted') {
            return redirect()->back()->with('error', 'Le document doit être converti en PDF pour être signé.');
        }

        $path = $request->file('signed_pdf')->store('signed_pdfs', 'public');
        $hash = hash_file('sha256', Storage::disk('public')->path($path));

        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $path,
            'hash' => $hash,
            'type' => 'pdf_signe_createur',
            'created_by' => Auth::id(),
        ]);

        $document->pdf_signe_createur = $path;
        $document->status = 'signing_validator';
        $document->current_role = 'validator';
        $document->save();

        DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'role' => 'creator'],
            [
                'user_id' => Auth::id(),
                'order' => 1,
                'signed_at' => now(),
            ]
        );

        Transmission::create([
            'document_id' => $document->id,
            'from_role' => 'creator',
            'to_role' => 'validator',
            'action' => 'sign',
            'status' => 'done',
            'sent_by' => Auth::id(),
        ]);

        $validators = User::where('role', 'validator')
            ->where('is_admin_approved', true)
            ->get();

        foreach ($validators as $validator) {
            $validator->notify(new DocumentTaskNotification(
                $document,
                'Le créateur a signé le document. Veuillez le télécharger, signer et renvoyer.',
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
                'message' => 'Document signé envoyé au validateur avec succès.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->route('documents.my')->with('success', 'Document signé envoyé au validateur avec succès.');
    }

    public function creatorSign(Request $request, $id): RedirectResponse|JsonResponse
    {
        $request->validate([
            'document_signe' => 'required|file|mimes:pdf|max:20480',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'creator' || ($document->created_by !== Auth::id() && $document->current_owner_id !== Auth::id())) {
            abort(403);
        }

        if (!in_array($document->status, ['ready_for_pdf', 'pdf_converted'])) {
            return redirect()->back()->with('error', 'Le document doit être prêt pour signature.');
        }

        $path = $request->file('document_signe')->store('documents/signes', 'public');

        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $path,
            'hash' => hash_file('sha256', Storage::disk('public')->path($path)),
            'type' => 'pdf_signe_createur',
            'created_by' => Auth::id(),
        ]);

        $document->pdf_signe_createur = $path;
        $document->status = 'signing_validator';
        $document->current_role = 'validator';
        $document->save();

        DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'role' => 'creator'],
            [
                'user_id' => Auth::id(),
                'order' => 1,
                'signed_at' => now(),
            ]
        );

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

        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $path,
            'hash' => hash_file('sha256', Storage::disk('public')->path($path)),
            'type' => 'pdf_signe_validateur',
            'created_by' => Auth::id(),
        ]);

        $document->pdf_signe_validateur = $path;
        $document->status = 'signing_approver';
        $document->current_role = 'approver';
        $document->save();

        DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'role' => 'validator'],
            [
                'user_id' => Auth::id(),
                'order' => 2,
                'signed_at' => now(),
            ]
        );

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

        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $path,
            'hash' => hash_file('sha256', Storage::disk('public')->path($path)),
            'type' => 'pdf_signe_approbateur',
            'created_by' => Auth::id(),
        ]);

        $document->pdf_signe_approbateur = $path;
        $document->status = 'signing_admin';
        $document->current_role = 'admin';
        $document->save();

        DocumentSignature::updateOrCreate(
            ['document_id' => $document->id, 'role' => 'approver'],
            [
                'user_id' => Auth::id(),
                'order' => 3,
                'signed_at' => now(),
            ]
        );

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

        DocumentVersion::create([
            'document_id' => $document->id,
            'revision' => $document->revision,
            'file_path' => $path,
            'hash' => hash_file('sha256', Storage::disk('public')->path($path)),
            'type' => 'pdf_signe_final',
            'created_by' => Auth::id(),
        ]);

        $document->pdf_signe_final = $path;
        $document->status = 'archived';
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
                'Votre document a été signé par tous et est maintenant finalisé.',
                'archived'
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
                'message' => 'Document signé et finalisé avec succès !',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document signé et finalisé avec succès !');
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
            'motif_rejet' => 'required|string',
            'deadline_correction' => 'nullable|date',
        ]);

        $document = Document::findOrFail($id);

        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        if (!in_array($document->status, ['validation_admin', 'signing_admin']) || $document->current_role !== 'admin') {
            return redirect()->back()->with('error', 'Action non autorisée.');
        }

        $document->status = 'EN_MODIFICATION';
        $document->current_role = 'creator';
        $document->commentaire_rejet = $request->motif_rejet;
        $document->deadline_correction = $request->deadline_correction;
        $document->save();

        LogTransmission($document, Auth::user(), 'admin', 'creator', 'reject', $request->motif_rejet);

        if ($document->creator) {
            $document->creator->notify(new DocumentTaskNotification(
                $document,
                'Votre document a été refusé par l\'administrateur. Veuillez le modifier et le renvoyer au validateur.',
                'EN_MODIFICATION'
            ));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'admin_rejected',
            'auditable_type' => Document::class,
            'auditable_id' => $document->id,
            'payload' => ['commentaire' => $request->motif_rejet, 'deadline' => $request->deadline_correction],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Document refusé et créateur notifié.',
                'data' => $document->refresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Document refusé et créateur notifié.');
    }

    private function reviewerDashboard(string $role, string $view, ?string $filter = null): View
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->limit(6)->get();

        $validationStatuses = $role === 'approver'
            ? ['in_approbation', 'approbation']
            : ['in_validation', 'EN_VALIDATION'];

        $signingStatuses = $role === 'approver'
            ? ['signing_approver', 'SIGNATURE_APPROBATEUR']
            : ['signing_validator', 'SIGNATURE_VALIDATEUR'];

        $baseQuery = Document::with(['creator']);

        if ($filter === 'pending') {
            $documents = (clone $baseQuery)
                ->whereIn('status', array_merge($validationStatuses, $signingStatuses))
                ->where('current_role', $role)
                ->latest()
                ->paginate(20);
        } elseif ($filter === 'processed') {
            $documents = (clone $baseQuery)
                ->where(function ($q) use ($user, $role) {
                    if ($role === 'validator') {
                        $q->where('validated_by', $user->id);
                    } else {
                        $q->where('approved_by', $user->id);
                    }
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
                ->where(function ($query) use ($user, $role) {
                    $query->whereHas('signatures', function ($q) use ($user, $role) {
                            $q->where('user_id', $user->id)->where('role', $role);
                        })
                        ->orWhere('validated_by', $user->id)
                        ->orWhere('approved_by', $user->id)
                        ->orWhereHas('transmissions', function ($q) use ($role) {
                            $q->where('to_role', $role);
                        })
                        ->orWhere(function ($q) use ($user, $role) {
                            $q->where('current_role', $role)
                              ->where('created_by', '!=', $user->id);
                        });
                })
                ->latest()
                ->paginate(20);
        }

        // Documents awaiting validation (valider/rejeter)
        $enAttenteValidation = Document::whereIn('status', $validationStatuses)
            ->where('current_role', $role)
            ->with(['creator'])
            ->latest()
            ->get();

        // Documents awaiting signature
        $enAttenteSignature = Document::whereIn('status', $signingStatuses)
            ->where('current_role', $role)
            ->with(['creator'])
            ->latest()
            ->get();

        // All alertes = validation + signature
        $alertes = $enAttenteValidation->merge($enAttenteSignature)->sortBy(function ($doc) {
            if (!$doc->deadline) {
                return 2;
            }
            if ($doc->deadline->isPast()) {
                return 0;
            }
            if ($doc->deadline->isBefore(now()->addDays(2))) {
                return 1;
            }
            return 2;
        })->take(10);

        // Documents to verify (same as enAttenteValidation)
        $documentsAVerifier = $enAttenteValidation;

        $processedQuery = Document::with(['creator'])
            ->whereHas('signatures', function ($query) use ($user, $role) {
                $query->where('user_id', $user->id)->where('role', $role);
            });

        $processedDocuments = (clone $processedQuery)->latest('updated_at')->limit(6)->get();

        // Stats
        $enAttenteValidationCount = Document::whereIn('status', $validationStatuses)
            ->where('current_role', $role)->count();

        $enAttenteSignatureCount = Document::whereIn('status', $signingStatuses)
            ->where('current_role', $role)->count();

        if ($role === 'validator') {
            $validatedCount = Document::where('validated_by', $user->id)->count();
        } elseif ($role === 'approver') {
            $validatedCount = Document::where('approved_by', $user->id)->count();
        } else {
            $validatedCount = 0;
        }

        $rejectedCount = Document::whereHas('transmissions', function ($query) use ($user) {
            $query->where('sent_by', $user->id)->where('action', 'reject');
        })->count();

        $stats = [
            'en_attente_validation' => $enAttenteValidationCount,
            'en_attente_signature' => $enAttenteSignatureCount,
            'processed' => $validatedCount,
            'rejected' => $rejectedCount,
            'notifications' => $user->unreadNotifications()->count(),
        ];

        return view($view, compact(
            'documents',
            'stats',
            'filter',
            'notifications',
            'alertes',
            'enAttenteValidation',
            'enAttenteSignature',
            'documentsAVerifier',
            'processedDocuments'
        ));
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
