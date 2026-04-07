<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Document $document): bool
    {
        // Admin voit tout
        if ($user->role === 'admin') return true;
        // Créateur voit ses propres docs
        if ($user->id === $document->created_by) return true;
        // Checker : docs en validation à son étape
        if ($user->role === 'checker' && $document->status === 'in_validation' && $document->current_role === 'checker') return true;
        // Approver : docs en validation à son étape
        if ($user->role === 'approver' && $document->status === 'in_validation' && $document->current_role === 'approver') return true;
        return false;
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->id === $document->created_by
            && in_array($document->status, ['draft', 'rejected'], true);
    }

    public function requestDeletion(User $user, Document $document): bool
    {
        return $user->id === $document->created_by
            && $document->status === 'draft';
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->role === 'admin';
    }

    public function assignCode(User $user, Document $document): bool
    {
        return $user->role === 'admin';
    }
}
