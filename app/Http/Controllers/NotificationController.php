<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $data = $notification->data;
        $notification->markAsRead();

        $type = $data['type'] ?? null;
        $documentId = $data['document_id'] ?? null;

        return match($type) {
            'codification' => redirect()->route('admin.documents.codification'),
            'signature_finale', 'pret_signature', 'approved' => redirect()->route('admin.dashboard'),
            'new_task', 'validation' => redirect()->route('workflow.validator.index'),
            'approbation' => redirect()->route('workflow.approver.index'),
            'rejected' => redirect()->route('documents.creator.index'),
            default => redirect()->route('dashboard'),
        };
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }
}