<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentTaskNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $messageText,
        public string $type = 'task'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = match ($notifiable->role) {
            'creator' => route('documents.creator.index', ['status' => 'ready_for_pdf']),
            'validator' => route('workflow.validator.index', ['filter' => 'pending']),
            'approver' => route('workflow.approver.index', ['filter' => 'pending']),
            'admin' => route('admin.dashboard'),
            default => url('/'),
        };

        return (new MailMessage)
            ->subject('Mise a jour documentaire')
            ->line($this->messageText)
            ->line('Document : '.$this->document->name)
            ->action('Consulter', $url)
            ->line('Le suivi complet reste disponible dans la GED.');
    }

    public function toArray(object $notifiable): array
    {
        $url = match ($notifiable->role) {
            'creator' => route('documents.creator.index', ['status' => 'ready_for_pdf']),
            'validator' => route('workflow.validator.index', ['filter' => 'pending']),
            'approver' => route('workflow.approver.index', ['filter' => 'pending']),
            'admin' => route('admin.dashboard'),
            default => url('/my-documents'),
        };

        return [
            'document_id' => $this->document->id,
            'type' => $this->type,
            'message' => $this->messageText,
            'status' => $this->document->status,
            'url' => $url,
        ];
    }
}
