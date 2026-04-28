<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau document assigne')
            ->line('Un nouveau document vous a ete assigne par l\'administrateur.')
            ->line('Document : '.$this->document->name)
            ->line('Code : '.$this->document->code)
            ->action('Voir le document', route('documents.creator.index'))
            ->line('Vous pouvez le modifier et l\'envoyer au validateur.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'type' => 'document_assigned',
            'message' => 'Un nouveau document vous a ete assigne par l\'administrateur. Vous pouvez le modifier et l\'envoyer au validateur.',
            'status' => $this->document->status,
            'url' => route('documents.creator.index'),
        ];
    }
}
