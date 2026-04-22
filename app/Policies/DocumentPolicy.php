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
        if ($user->role === 'admin') {
            return true;
        }
        // Créateur voit ses propres docs
        if ($user->id === $document->created_by) {
            return true;
        }
        // Validator : docs en validation à son étape
        if ($user->role === 'validator' && $document->status === 'in_validation' && $document->current_role === 'validator') {
            return true;
        }
        // Approver : docs en validation à son étape
        if ($user->role === 'approver' && $document->status === 'in_validation' && $document->current_role === 'approver') {
            return true;
        }

        return false;
    }

    public function download(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function update(User $user, Document $document): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $document->created_by
            && in_array($document->status, ['draft', 'rejected'], true);
    }

    public function requestDeletion(User $user, Document $document): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $document->created_by
            && $document->status === 'draft';
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['creator', 'admin'], true);
    }

    public function assignCode(User $user, Document $document): bool
    {
        return $user->role === 'admin';
    }
}
