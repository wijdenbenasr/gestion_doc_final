<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DailyPendingWorkflowAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendDailyOpenWorkflowAlerts extends Command
{
    protected $signature = 'documents:send-daily-open-alerts';

    protected $description = 'Examiner les taches et actions documentaires non fermees et envoyer un rappel quotidien aux acteurs concernes';

    public function handle(): int
    {
        $totalRecipients = 0;
        $totalDocuments = 0;

        foreach ([
            $this->sendCreatorAlerts(),
            $this->sendRoleAlerts('validator'),
            $this->sendRoleAlerts('approver'),
            $this->sendAdminAlerts(),
        ] as [$recipients, $documents]) {
            $totalRecipients += $recipients;
            $totalDocuments += $documents;
        }

        $this->info("Alertes quotidiennes envoyees a {$totalRecipients} acteur(s) pour {$totalDocuments} document(s) en attente.");

        return self::SUCCESS;
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function sendCreatorAlerts(): array
    {
        $documentsByCreator = Document::query()
            ->with('creator')
            ->where('current_role', 'creator')
            ->where(function ($query) {
                $query->whereIn('status', ['rejected', 'ready_for_pdf'])
                    ->orWhere(function ($draftQuery) {
                        $draftQuery->where('status', 'draft')
                            ->whereNotNull('code');
                    });
            })
            ->get()
            ->groupBy('created_by');

        $recipients = 0;
        $documentsCount = 0;

        foreach ($documentsByCreator as $documents) {
            $creator = $documents->first()?->creator;

            if (! $this->isActiveUser($creator)) {
                continue;
            }

            $creator->notify($this->buildNotification(
                'creator',
                $documents,
                route('documents.creator.index')
            ));

            $recipients++;
            $documentsCount += $documents->count();
        }

        return [$recipients, $documentsCount];
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function sendRoleAlerts(string $role): array
    {
        $documents = Document::query()
            ->with('creator')
            ->where('status', 'in_validation')
            ->where('current_role', $role)
            ->get();

        if ($documents->isEmpty()) {
            return [0, 0];
        }

        $recipients = 0;

        foreach ($this->activeUsersByRole($role) as $user) {
            $user->notify($this->buildNotification(
                $role,
                $documents,
                $this->dashboardUrlForRole($role)
            ));

            $recipients++;
        }

        return [$recipients, $documents->count()];
    }

    /**
     * @return array{0:int,1:int}
     */
    protected function sendAdminAlerts(): array
    {
        $documents = Document::query()
            ->with('creator')
            ->where(function ($query) {
                $query->where(function ($codificationQuery) {
                    $codificationQuery->where('status', 'pending_codification')
                        ->where('current_role', 'admin');
                })->orWhere(function ($finalValidationQuery) {
                    $finalValidationQuery->where('status', 'in_validation')
                        ->where('current_role', 'admin');
                });
            })
            ->get();

        if ($documents->isEmpty()) {
            return [0, 0];
        }

        $recipients = 0;

        foreach ($this->activeUsersByRole('admin') as $user) {
            $user->notify($this->buildNotification(
                'admin',
                $documents,
                route('admin.dashboard')
            ));

            $recipients++;
        }

        return [$recipients, $documents->count()];
    }

    protected function buildNotification(string $role, Collection $documents, string $dashboardUrl): DailyPendingWorkflowAlert
    {
        $orderedDocuments = $documents
            ->sortBy(function (Document $document) {
                return sprintf(
                    '%010d-%010d',
                    $document->deadline?->getTimestamp() ?? 9999999999,
                    $document->updated_at?->getTimestamp() ?? 9999999999
                );
            })
            ->values();

        $dueSoonLimit = now()->copy()->addDays(2);

        return new DailyPendingWorkflowAlert(
            recipientRole: $role,
            pendingCount: $orderedDocuments->count(),
            overdueCount: $orderedDocuments->filter(fn (Document $document) => $document->deadline?->isPast())->count(),
            dueSoonCount: $orderedDocuments->filter(function (Document $document) use ($dueSoonLimit) {
                return $document->deadline
                    && ! $document->deadline->isPast()
                    && $document->deadline->lessThanOrEqualTo($dueSoonLimit);
            })->count(),
            breakdown: $orderedDocuments
                ->countBy(fn (Document $document) => $this->taskLabel($document, $role))
                ->all(),
            documentsPreview: $orderedDocuments
                ->take(5)
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'name' => $document->name,
                    'code' => $document->code,
                    'status' => $document->status,
                    'task' => $this->taskLabel($document, $role),
                    'deadline' => $document->deadline?->format('d/m/Y H:i'),
                    'creator' => trim(collect([$document->creator?->name, $document->creator?->prenom])->filter()->implode(' ')),
                ])
                ->values()
                ->all(),
            dashboardUrl: $dashboardUrl,
        );
    }

    protected function taskLabel(Document $document, string $role): string
    {
        return match (true) {
            $role === 'creator' && $document->status === 'rejected' => 'correction et renvoi',
            $role === 'creator' && $document->status === 'ready_for_pdf' => 'conversion PDF et signature',
            $role === 'creator' && $document->status === 'draft' => 'soumission au validateur',
            $role === 'validator' => 'validation',
            $role === 'approver' => 'approbation',
            $role === 'admin' && $document->status === 'pending_codification' => 'codification',
            $role === 'admin' => 'traitement final',
            default => 'traitement',
        };
    }

    protected function dashboardUrlForRole(string $role): string
    {
        return match ($role) {
            'validator' => route('workflow.validator.index', ['filter' => 'pending']),
            'approver' => route('workflow.approver.index', ['filter' => 'pending']),
            default => route('documents.creator.index'),
        };
    }

    protected function activeUsersByRole(string $role): Collection
    {
        return User::query()
            ->where('role', $role)
            ->where('is_admin_approved', true)
            ->whereNotNull('email_verified_at')
            ->get();
    }

    protected function isActiveUser(?User $user): bool
    {
        return $user !== null
            && $user->is_admin_approved
            && $user->email_verified_at !== null;
    }
}
