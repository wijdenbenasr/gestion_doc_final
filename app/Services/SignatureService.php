<?php

namespace App\Services;

use App\Events\DocumentSigned;
use App\Models\Archive;
use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\Transmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SignatureService
{
    public function __construct(
        protected DocumentNotificationService $documentNotificationService
    ) {}

    public function computeHash(Document $document): string
    {
        return hash('sha256', Storage::disk('private')->get($document->file_path));
    }

    public function canSign(Document $document, User $user): bool
    {
        if (
            $user->role === 'creator'
            && $document->created_by === $user->id
            && $document->status === 'draft'
            && ! empty($document->code)
        ) {
            return $document->current_role === 'creator';
        }

        if (! in_array($document->status, ['in_validation', 'approved'], true)) {
            return false;
        }

        return $document->current_role === $user->role;
    }

    public function sign(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            if (! $this->canSign($document, $user)) {
                abort(403, 'Vous n etes pas autorise a signer ce document maintenant.');
            }

            $hash = $this->computeHash($document);

            if ($document->hash && $document->hash !== $hash) {
                abort(409, 'Integrite compromise : le fichier a ete modifie.');
            }

            if (! $document->hash) {
                $document->hash = $hash;
                $document->save();
            }

            DocumentSignature::updateOrCreate(
                ['document_id' => $document->id, 'role' => $user->role],
                [
                    'user_id' => $user->id,
                    'hash' => $hash,
                    'order' => $this->orderForRole($user->role),
                    'signed_at' => now(),
                ]
            );

            DocumentSigned::dispatch($document, $user);

            match ($user->role) {
                'creator' => $document->status === 'ready_for_pdf'
                    ? $this->advanceTo(
                        $document,
                        $user,
                        'admin',
                        'finalize',
                        'Le document PDF a ete signe par le createur et attend votre validation finale.'
                    )
                    : $this->advanceTo(
                        $document,
                        $user,
                        'validator',
                        'submit',
                        'Un document code a ete signe par le createur et attend votre validation.'
                    ),
                'validator' => $this->advanceTo(
                    $document,
                    $user,
                    'approver',
                    'validate',
                    'Un document a ete valide et attend votre approbation.'
                ),
                'approver' => $this->advanceTo(
                    $document,
                    $user,
                    'admin',
                    'validate',
                    'Un document approuve attend votre validation finale.'
                ),
                'admin' => $this->handleAdminSign($document, $user),
                default => null,
            };

            $document->save();
        });
    }

    private function handleAdminSign(Document $document, User $user): void
    {
        if (DocumentSignature::where('document_id', $document->id)->where('role', 'admin')->exists()) {
            // Second admin sign, finalize
            $this->finalize($document, $user);
        } else {
            // First admin sign, send to creator for PDF
            $this->advanceTo(
                $document,
                $user,
                'creator',
                'pdf_conversion',
                'Le document a ete valide. Veuillez le convertir en PDF, le signer et le renvoyer.'
            );
        }
    }

    public function advanceWithoutSigning(Document $document, User $user): void
    {
        match ($user->role) {
            'validator' => $this->advanceTo(
                $document,
                $user,
                'approver',
                'validate',
                'Un document a ete valide et attend votre approbation.'
            ),
            'approver' => $this->advanceTo(
                $document,
                $user,
                'admin',
                'validate',
                'Un document approuve attend votre validation finale.'
            ),
            'admin' => $this->advanceTo(
                $document,
                $user,
                'creator',
                'pdf_conversion',
                'Le document a ete valide. Veuillez le convertir en PDF, le signer et le renvoyer.'
            ),
            default => null,
        };

        $document->save();
    }

    private function advanceTo(
        Document $document,
        User $user,
        string $nextRole,
        string $action,
        string $message
    ): void {
        if ($nextRole === 'creator' && $action === 'pdf_conversion') {
            $document->status = 'ready_for_pdf';
            $document->current_owner_id = $document->created_by;
        } else {
            if ($user->role === 'creator') {
                $document->status = 'in_validation';
            }
            $document->current_owner_id = null;
        }

        $document->current_role = $nextRole;

        Transmission::create([
            'document_id' => $document->id,
            'from_role' => $user->role,
            'to_role' => $nextRole,
            'action' => $action,
            'status' => 'done',
            'sent_by' => $user->id,
        ]);

        if ($nextRole === 'creator' && $document->creator) {
            $this->documentNotificationService->notifyUser($document->creator, $document, $message, 'new_task');

            return;
        }

        $this->documentNotificationService->notifyRole($nextRole, $document, $message, 'new_task');
    }

    private function finalize(Document $document, User $user): void
    {
        $document->is_fully_signed = true;
        $document->status = 'finalized';
        $document->current_role = null;
        $document->current_owner_id = null;

        Archive::create([
            'document_id' => $document->id,
            'archived_by' => $user->id,
            'archived_at' => now(),
        ]);

        Transmission::create([
            'document_id' => $document->id,
            'from_role' => 'admin',
            'to_role' => 'archive',
            'action' => 'finalize',
            'status' => 'done',
            'comment' => 'Document finalise et archive automatiquement.',
            'sent_by' => $user->id,
        ]);

        $this->documentNotificationService->notifyCreatorFinalized($document);
    }

    private function orderForRole(string $role): int
    {
        return match ($role) {
            'creator' => 1,
            'validator' => 2,
            'approver' => 3,
            'admin' => 4,
            default => 0,
        };
    }
}
