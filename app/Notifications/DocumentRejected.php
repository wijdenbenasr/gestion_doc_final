<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejected extends Notification
{
    use Queueable;

    public function __construct(public Document $document, public string $messageText) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document refusé')
            ->line('Votre document '.$this->document->name.' a été refusé.')
            ->line('Motif : '.$this->messageText)
            ->action('Consulter le document', url('/documents/creator'))
            ->line('Merci de corriger le document puis de relancer le cycle de validation.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'type' => 'rejected',
            'message' => $this->messageText,
        ];
    }
}
