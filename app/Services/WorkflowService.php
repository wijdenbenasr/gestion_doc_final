<?php

namespace App\Services;

use App\Events\DocumentRejected;
use App\Events\DocumentSubmitted;
use App\Events\DocumentValidated;
use App\Models\Document;
use App\Models\Transmission;
use App\Models\User;
use App\Repositories\Interfaces\DocumentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function __construct(
        protected DocumentRepositoryInterface $documentRepository,
        protected SignatureService $signatureService,
        protected DocumentNotificationService $documentNotificationService
    ) {}

    public function submit(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            $this->documentRepository->update($document, [
                'status' => 'pending_codification',
                'current_role' => 'admin',
                'current_owner_id' => null,
            ]);

            $this->logTransmission($document, $user, 'creator', 'admin', 'submit');
            $this->documentNotificationService->notifyRole(
                'admin',
                $document,
                'Un document a ete soumis pour codification.',
                'new_task'
            );

            DocumentSubmitted::dispatch($document, $user);
        });
    }

    public function codify(Document $document, User $admin, string $code): void
    {
        DB::transaction(function () use ($document, $admin, $code) {
            $this->documentRepository->update($document, [
                'code' => $code,
                'status' => 'draft',
                'current_role' => 'creator',
                'current_owner_id' => $document->created_by,
            ]);

            if ($document->creator) {
                $this->documentNotificationService->notifyUser(
                    $document->creator,
                    $document,
                    'Votre document a ete codifie. Vous pouvez maintenant le signer et l envoyer au validateur.',
                    'coded'
                );
            }

            $this->logTransmission($document, $admin, 'admin', 'creator', 'codify', "Code attribue : {$code}");
        });
    }

    public function submitToValidator(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            $this->signatureService->sign($document, $user);

            if ($document->creator) {
                $this->documentNotificationService->notifyUser(
                    $document->creator,
                    $document,
                    'Votre document a ete signe et soumis au validateur.',
                    'submitted'
                );
            }

            DocumentSubmitted::dispatch($document, $user);
        });
    }

    public function signAfterPdfAndSubmitToAdmin(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            $this->signatureService->sign($document, $user);
            DocumentSubmitted::dispatch($document, $user);
        });
    }

    public function validate(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            $this->signatureService->sign($document, $user);
            DocumentValidated::dispatch($document, $user);
        });
    }

    public function validateOnly(Document $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            $this->signatureService->advanceWithoutSigning($document, $user);
            DocumentValidated::dispatch($document, $user);
        });
    }

    public function reject(Document $document, User $user, string $message, ?string $deadline): void
    {
        DB::transaction(function () use ($document, $user, $message, $deadline) {
            $newRevision = $this->incrementRevision($document->revision);

            $this->documentRepository->update($document, [
                'status' => 'rejected',
                'current_role' => 'creator',
                'current_owner_id' => $document->created_by,
                'revision' => $newRevision,
                'deadline' => $deadline,
            ]);

            $this->logTransmission($document, $user, $user->role, 'creator', 'reject', $message);
            $this->documentNotificationService->notifyCreatorRejected($document, $message);

            DocumentRejected::dispatch($document, $user, [
                'message' => $message,
                'deadline' => $deadline,
            ]);
        });
    }

    private function logTransmission(
        Document $document,
        User $user,
        string $from,
        string $to,
        string $action,
        ?string $comment = null
    ): void {
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

    private function incrementRevision(string $revision): string
    {
        if (! str_contains($revision, '.')) {
            return $revision.'.1';
        }

        [$major, $minor] = explode('.', $revision, 2);

        return $major.'.'.((int) $minor + 1);
    }
}
