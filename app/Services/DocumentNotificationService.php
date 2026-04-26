<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Notifications\DocumentRejected;
use App\Notifications\DocumentSigned;
use App\Notifications\DocumentTaskNotification;
use Illuminate\Support\Collection;

class DocumentNotificationService
{
    public function notifyRole(string $role, Document $document, string $message, string $type = 'task'): void
    {
        $this->activeUsersByRole($role)->each(function (User $user) use ($document, $message, $type) {
            $user->notify(new DocumentTaskNotification($document, $message, $type));
            usleep(1100000);
        });
    }

    public function notifyUser(User $user, Document $document, string $message, string $type = 'task'): void
    {
        $user->notify(new DocumentTaskNotification($document, $message, $type));
        usleep(1100000);
    }

    public function notifyCreatorRejected(Document $document, string $message): void
    {
        if ($document->creator) {
            $document->creator->notify(new DocumentRejected($document, $message));
            usleep(1100000);
        }
    }

    public function notifyCreatorFinalized(Document $document): void
    {
        if ($document->creator) {
            $document->creator->notify(new DocumentSigned($document));
            usleep(1100000);
        }
    }

    protected function activeUsersByRole(string $role): Collection
    {
        return User::query()
            ->where('role', $role)
            ->where('is_admin_approved', true)
            ->whereNotNull('email_verified_at')
            ->get();
    }
}
