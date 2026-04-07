<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSigned extends Notification
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
            ->subject('Document signé')
            ->line('Le document '.$this->document->name.' a été signé.')
            ->line('Statut actuel : '.$this->document->status)
            ->action('Consulter le document', url('/documents/creator'))
            ->line('Cette notification confirme la signature numérique.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'type' => 'signed',
            'status' => $this->document->status,
        ];
    }
}
