<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentDeadlineExpired extends Notification
{
    use Queueable;

    public function __construct(public Document $document) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Deadline document dépassée')
            ->line('La deadline du document '.$this->document->name.' est dépassée.')
            ->line('Deadline : '.$this->document->deadline)
            ->action('Consulter le document', url('/documents/creator'))
            ->line('Le statut du document est passé à expiré.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'type' => 'deadline_expired',
            'deadline' => $this->document->deadline,
        ];
    }
}
