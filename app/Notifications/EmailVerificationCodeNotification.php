<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public int $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Code de vérification - ' . config('app.name'))
            ->line('Voici votre code de vérification pour votre inscription.')
            ->line('Votre code est : ' . $this->code)
            ->line('Ce code expirera dans 30 minutes.')
            ->line('Si vous n\'avez pas demandé ce code, vous pouvez ignorer cet email.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
        ];
    }
}
