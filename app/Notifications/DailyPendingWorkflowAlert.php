<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyPendingWorkflowAlert extends Notification
{
    use Queueable;

    public function __construct(
        public string $recipientRole,
        public int $pendingCount,
        public int $overdueCount,
        public int $dueSoonCount,
        public array $breakdown,
        public array $documentsPreview,
        public string $dashboardUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Rappel quotidien: '.$this->pendingCount.' action(s) documentaire(s) ouverte(s)')
            ->line($this->summaryMessage())
            ->line($this->breakdownMessage());

        if ($this->overdueCount > 0) {
            $mail->line($this->overdueCount.' element(s) sont deja hors delai.');
        }

        if ($this->dueSoonCount > 0) {
            $mail->line($this->dueSoonCount.' element(s) arrivent a echeance sous 48 heures.');
        }

        if ($this->documentsPreview !== []) {
            $mail->line('Apercu des priorites du moment :');

            foreach ($this->documentsPreview as $document) {
                $label = '- '.$document['task'].' : '.$document['name'];

                if (! empty($document['code'])) {
                    $label .= ' ('.$document['code'].')';
                }

                if (! empty($document['deadline'])) {
                    $label .= ' - deadline '.$document['deadline'];
                }

                $mail->line($label);
            }
        }

        return $mail
            ->action($this->actionLabel(), $this->dashboardUrl)
            ->line('Le suivi complet reste disponible dans la GED.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'daily_pending_summary',
            'message' => $this->summaryMessage(),
            'recipient_role' => $this->recipientRole,
            'pending_count' => $this->pendingCount,
            'overdue_count' => $this->overdueCount,
            'due_soon_count' => $this->dueSoonCount,
            'breakdown' => $this->breakdown,
            'documents' => $this->documentsPreview,
            'url' => $this->dashboardUrl,
        ];
    }

    protected function summaryMessage(): string
    {
        return 'Rappel quotidien: '.$this->pendingCount.' action(s) documentaire(s) restent ouvertes pour le role '.$this->roleLabel().'.';
    }

    protected function breakdownMessage(): string
    {
        $parts = [];

        foreach ($this->breakdown as $label => $count) {
            $parts[] = $count.' '.$label;
        }

        return 'Repartition: '.implode(', ', $parts).'.';
    }

    protected function actionLabel(): string
    {
        return match ($this->recipientRole) {
            'creator' => 'Ouvrir mes documents',
            'validator' => 'Ouvrir les validations',
            'approver' => 'Ouvrir les approbations',
            'admin' => 'Ouvrir le dashboard admin',
            default => 'Ouvrir la plateforme',
        };
    }

    protected function roleLabel(): string
    {
        return match ($this->recipientRole) {
            'creator' => 'createur',
            'validator' => 'validateur',
            'approver' => 'approbateur',
            'admin' => 'administrateur',
            default => $this->recipientRole,
        };
    }
}
