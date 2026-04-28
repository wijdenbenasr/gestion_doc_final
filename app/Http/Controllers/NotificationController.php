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

        $type   = $data['type']   ?? null;
        $status = $data['status'] ?? null;
        $role   = auth()->user()->role;

        if (!empty($data['url'])) {
            return redirect($data['url']);
        }

        return match(true) {
            in_array($type, ['codification', 'pending_codification']) => redirect()->route('admin.documents.codification'),
            in_array($type, ['validation_admin', 'signing_admin', 'signature_finale', 'approved']) => redirect()->route('admin.dashboard'),

            in_array($type, ['new_task', 'validation', 'in_validation']) && $role === 'validator'
                => redirect()->route('workflow.validator.index', ['filter' => 'pending']),
            in_array($type, ['signing_validator']) && $role === 'validator'
                => redirect()->route('workflow.validator.index', ['filter' => 'pending']),

            in_array($type, ['approbation']) && $role === 'approver'
                => redirect()->route('workflow.approver.index', ['filter' => 'pending']),
            in_array($type, ['signing_approver']) && $role === 'approver'
                => redirect()->route('workflow.approver.index', ['filter' => 'pending']),

            in_array($type, ['rejected']) => redirect()->route('documents.creator.index', ['status' => 'rejected']),
            in_array($type, ['ready_for_pdf', 'archived']) => redirect()->route('documents.creator.index', ['status' => $type]),

            $role === 'admin'     => redirect()->route('admin.dashboard'),
            $role === 'validator' => redirect()->route('workflow.validator.index', ['filter' => 'pending']),
            $role === 'approver'  => redirect()->route('workflow.approver.index', ['filter' => 'pending']),
            $role === 'creator'   => redirect()->route('documents.creator.index'),

            default => redirect()->route('dashboard'),
        };
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }
}