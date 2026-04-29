@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Toutes les notifications</div>
            <div class="card-sub">{{ auth()->user()->unreadNotifications->count() }} non lues</div>
        </div>
        @if(auth()->user()->unreadNotifications->count() > 0)
            <form method="POST" action="{{ route('notifications.markAllRead') }}">
                @csrf
                <button type="submit" class="btn btn-sm">Tout marquer comme lu</button>
            </form>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div style="text-align:center;padding:3rem;color:var(--muted);">
            <i class="fas fa-bell-slash fa-3x mb-3" style="opacity:.3;display:block;"></i>
            <div>Aucune notification</div>
        </div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Message</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($notifications as $notification)
                    <tr>
                        <td>
                            <i class="fas fa-file-alt" style="color:#f59e0b;margin-right:.5rem;"></i>
                            {{ $notification->data['message'] ?? 'Notification' }}
                        </td>
                        <td>
                            @php
                                $type = $notification->data['type'] ?? 'default';
$typeLabels = [

    'codification' => 'Codification',

    'validation' => 'Validation',

    'in_validation' => 'En validation',

    'approbation' => 'Approbation',

    'validation_admin' => 'Validation finale admin',

    'signing_validator' => 'Signature validateur',

    'signing_approver' => 'Signature approbateur',

    'signing_admin' => 'Signature admin',

    'document_assigned' => 'Assignation',

    'archived' => 'Archivé',

    'ready_for_pdf' => 'Prêt pour PDF',

    'pdf_converted' => 'PDF converti',

    'coded' => 'Codifié',

    'rejected' => 'Rejeté',

    'EN_MODIFICATION' => 'En modification',

    'default' => 'General',

];
                            @endphp
                            {{ $typeLabels[$type] ?? $typeLabels['default'] }}
                        </td>
                        <td style="font-size:.72rem;color:var(--muted);">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            @if($notification->read_at)
                                <span class="badge badge-muted">Lu</span>
                            @else
                                <span class="badge badge-attente">Non lu</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection



