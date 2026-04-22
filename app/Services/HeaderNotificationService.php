<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;

class HeaderNotificationService
{
    /**
     * @return array{
     *     unread_count: int,
     *     has_dropdown: bool,
     *     items: array<int, array{title:string,meta:string,type:string,url:string}>
     * }
     */
    public function buildFor(?User $user): array
    {
        if (! $user) {
            return $this->emptyState();
        }

        return match ($user->role) {
            'creator' => $this->forCreator($user),
            'validator' => $this->forReviewer($user, 'validator', 'Validation requise : ', 'workflow.validator.index'),
            'approver' => $this->forReviewer($user, 'approver', 'Approbation requise : ', 'workflow.approver.index'),
            'admin' => $this->forAdmin(),
            default => $this->emptyState(),
        };
    }

    /**
     * @return array{
     *     unread_count: int,
     *     has_dropdown: bool,
     *     items: array<int, array{title:string,meta:string,type:string,url:string}>
     * }
     */
    private function forCreator(User $user): array
    {
        $url = route('documents.creator.index', ['status' => 'rejected']);
        $documents = Document::query()
            ->where('created_by', $user->id)
            ->where('status', 'rejected')
            ->latest()
            ->limit(3)
            ->get();

        $items = $documents->map(function (Document $document) use ($url): array {
            return [
                'title' => 'Document rejete : '.$document->name,
                'meta' => 'A corriger et renvoyer',
                'type' => 'urgent',
                'url' => $url,
            ];
        })->all();

        return [
            'unread_count' => Document::query()
                ->where('created_by', $user->id)
                ->where('status', 'rejected')
                ->count(),
            'has_dropdown' => true,
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     unread_count: int,
     *     has_dropdown: bool,
     *     items: array<int, array{title:string,meta:string,type:string,url:string}>
     * }
     */
    private function forReviewer(User $user, string $role, string $titlePrefix, string $routeName): array
    {
        $url = route($routeName, ['filter' => 'pending']);
        $documents = Document::query()
            ->with('creator')
            ->where('status', 'in_validation')
            ->where('current_role', $role)
            ->latest()
            ->limit(3)
            ->get();

        $items = $documents->map(function (Document $document) use ($titlePrefix, $url): array {
            $metaParts = [
                $document->code ?: 'Sans code',
                $document->creator->name ?? 'Createur inconnu',
            ];

            if ($document->deadline) {
                $metaParts[] = 'Deadline '.$document->deadline->format('d/m/Y');
            }

            return [
                'title' => $titlePrefix.$document->name,
                'meta' => implode(' | ', $metaParts),
                'type' => $document->deadline && $document->deadline->isPast()
                    ? 'urgent'
                    : ($document->deadline && $document->deadline->isBefore(now()->addDays(2)) ? 'warning' : ''),
                'url' => $url,
            ];
        })->all();

        return [
            'unread_count' => Document::query()
                ->where('status', 'in_validation')
                ->where('current_role', $role)
                ->count(),
            'has_dropdown' => true,
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     unread_count: int,
     *     has_dropdown: bool,
     *     items: array<int, array{title:string,meta:string,type:string,url:string}>
     * }
     */
    private function forAdmin(): array
    {
        $pendingCodification = Document::query()
            ->with('creator')
            ->where('status', 'pending_codification')
            ->latest()
            ->limit(2)
            ->get();

        $pendingUsers = User::query()
            ->where('is_admin_approved', false)
            ->latest()
            ->limit(2)
            ->get();

        $items = [];

        foreach ($pendingCodification as $document) {
            $items[] = [
                'title' => 'Codification requise : '.$document->name,
                'meta' => 'Par '.($document->creator->name ?? 'Inconnu'),
                'type' => 'urgent',
                'url' => route('admin.documents.codification'),
            ];
        }

        foreach ($pendingUsers as $pendingUser) {
            $items[] = [
                'title' => 'Compte en attente : '.$pendingUser->name,
                'meta' => 'Validation admin requise',
                'type' => 'warning',
                'url' => route('admin.users.pending'),
            ];
        }

        return [
            'unread_count' => Document::query()->where('status', 'pending_codification')->count()
                + User::query()->where('is_admin_approved', false)->count(),
            'has_dropdown' => true,
            'items' => $items,
        ];
    }

    /**
     * @return array{
     *     unread_count: int,
     *     has_dropdown: bool,
     *     items: array<int, array{title:string,meta:string,type:string,url:string}>
     * }
     */
    private function emptyState(): array
    {
        return [
            'unread_count' => 0,
            'has_dropdown' => false,
            'items' => [],
        ];
    }
}
