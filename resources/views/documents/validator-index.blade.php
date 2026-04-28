@extends('layouts.app')

@section('title', 'Documents a valider')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" style="background:rgba(34,197,94,0.15);border:1px solid #22c55e;color:#22c55e;border-radius:8px;" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" style="background:rgba(239,68,68,0.15);border:1px solid #ef4444;color:#ef4444;border-radius:8px;" role="alert">
        <i class="fas fa-times-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
<div class="cards-row" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 1rem;">
    <a href="{{ route('workflow.validator.index') }}" class="stat-card {{ !$filter || $filter === 'pending' ? 'active' : '' }}">
        <div class="stat-label">En attente</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $stats['pending'] }}</div>
        <div class="stat-meta">Documents a traiter maintenant</div>
    </a>
    <a href="{{ route('workflow.validator.index', ['filter' => 'processed']) }}" class="stat-card {{ $filter === 'processed' ? 'active' : '' }}">
        <div class="stat-label">Validés</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['processed'] }}</div>
        <div class="stat-meta">Historique personnel</div>
    </a>
    <a href="{{ route('workflow.validator.index', ['filter' => 'rejected']) }}" class="stat-card {{ $filter === 'rejected' ? 'active' : '' }}">
        <div class="stat-label">Rejetés</div>
        <div class="stat-value" style="color:#f87171;">{{ $stats['rejected'] }}</div>
        <div class="stat-meta">Documents retournes au createur</div>
    </a>
    <a href="#notifications" class="stat-card">
        <div class="stat-label">Notifications</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $stats['notifications'] }}</div>
        <div class="stat-meta">Non lues</div>
    </a>
</div>

<div class="cards-row" style="grid-template-columns: 1fr 1fr; gap: 1rem; flex-grow: 1; margin-bottom: 1rem;">
    <div class="card" id="recent-alerts" style="min-height: 180px;">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;margin-right:.5rem;"></i>Alertes prioritaires</div>
                <div class="card-sub">Documents a traiter en priorite.</div>
            </div>
        </div>
        @php
            $showAlertes = isset($alertes) && $alertes->count() > 0;
        @endphp

        @if($showAlertes)
            <div style="display:grid;gap:.4rem;">
                @forelse($alertes as $doc)
                    @php
                        $isUrgent = $doc->deadline && $doc->deadline->isPast();
                        $isWarning = !$isUrgent && $doc->deadline && $doc->deadline->isBefore(now()->addDays(2));
                        $badgeClass = $isUrgent ? 'badge-danger' : ($isWarning ? 'badge-warning' : 'badge-info');
                    @endphp
                    <div id="validate-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
                        <div class="modal-content" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
                            <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                                <h4 style="margin:0;">✍️ Signer et envoyer le document</h4>
                                <button type="button" class="btn-close btn-close-white" onclick="closeValidateModal('{{ $doc->id }}')"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.validator.sign', $doc) }}">
                                    @csrf
                                    <div class="mb-3 p-3 rounded" style="background:rgba(255,255,255,0.05)">
                                        <small class="text-muted">Document :</small>
                                        <p class="mb-0 fw-bold">{{ $doc->name }}</p>
                                        <small class="text-muted">Code : {{ $doc->code }}</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">📎 Televerser le document signe *</label>
                                        <input type="file" name="document_signe" accept=".pdf" required class="form-control" style="background:#0f172a; color:white;">
                                        <small class="text-muted">Format accepte : PDF</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">💬 Commentaire (optionnel)</label>
                                        <textarea name="commentaire" rows="3" class="form-control" style="background:#0f172a; color:white;" placeholder="Ajouter un commentaire..."></textarea>
                                    </div>
                                    <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                                        <button type="button" class="btn btn-secondary" onclick="closeValidateModal('{{ $doc->id }}')">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Signer et envoyer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div style="padding:.6rem;border-radius:.4rem;background:rgba({{ $isUrgent ? '239,68,68' : ($isWarning ? '245,158,11' : '56,189,248') }},0.1);border-left:3px solid {{ $isUrgent ? 'var(--danger)' : ($isWarning ? 'var(--warning)' : 'var(--info)') }};">
                        <div style="display:flex;justify-content:space-between;align-items:start;gap:.75rem;margin-bottom:.3rem;">
                            <div>
                                <div style="font-weight:600;font-size:.85rem;">{{ $doc->name }}</div>
                                <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
                                    {{ $doc->code ?: 'Sans code' }} | Par {{ $doc->creator->name ?? 'Inconnu' }} | {{ $doc->revision }}
                                </div>
                            </div>
                            <span class="badge {{ $badgeClass }}" style="font-size:.7rem;white-space:nowrap;">
                                @if($isUrgent)
                                    URGENT
                                @elseif($isWarning)
                                    WARNING
                                @else
                                    PRET POUR SIGNATURE
                                @endif
                            </span>
                        </div>
                        <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Telecharger</a>
                            <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openValidateModal('{{ $doc->id }}')">Signer</button>
                        </div>
                    </div>
                @empty
                    <div style="color:var(--muted);padding:1rem;text-align:center;">
                        <i class="fas fa-check-circle fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                        Aucun document pret pour signature.
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    @php
        $showDocumentsAValider = isset($documentsAValider) && $documentsAValider->count() > 0;
    @endphp

    <div class="card" style="min-height: 180px;">
        <div class="card-header">
            <div>
                <div class="card-title">
                    <i class="fas fa-file-search" style="color:#f59e0b;margin-right:.5rem;"></i>Documents a verifier
                </div>
                <div class="card-sub">
                    Documents en attente de votre validation (Valider/Rejeter)
                </div>
            </div>
            <span class="badge badge-warning" style="font-size:.8rem;padding:.3rem .7rem;">
                {{ $showDocumentsAValider ? $documentsAValider->count() : 0 }} document(s)
            </span>
        </div>

        @if($showDocumentsAValider)
            <div style="display:grid;gap:.4rem;">
                @forelse($documentsAValider as $doc)
                    @php
                        $isUrgent = $doc->deadline && $doc->deadline->isPast();
                        $isWarning = !$isUrgent && $doc->deadline && $doc->deadline->isBefore(now()->addDays(2));
                        $badgeClass = $isUrgent ? 'badge-danger' : ($isWarning ? 'badge-warning' : 'badge-info');
                    @endphp
                    <div id="confirm-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
                        <div class="modal-content" style="background:#1f2937;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:400px;">
                            <h4 style="margin-bottom:1rem;">Confirmer la validation</h4>
                            <p style="margin-bottom:1.5rem;color:#9ca3af;">Voulez-vous valider le document "<strong>{{ $doc->name }}</strong>" ?</p>
                            <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                                <button type="button" class="btn btn-ghost" onclick="closeConfirmModal('{{ $doc->id }}')">Annuler</button>
                                <form action="{{ route('workflow.validator.validate', $doc) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="background:rgba(34,197,94,0.2);border:1px solid rgba(34,197,94,0.5);">Confirmer</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div id="reject-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
                        <div class="modal-content" style="background:#1f2937;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:400px;">
                            <h4 style="margin-bottom:1rem;">Rejeter le document</h4>
                            <form action="{{ route('workflow.validator.reject', $doc) }}" method="POST">
                                @csrf
                                <div style="margin-bottom:1rem;">
                                    <label style="display:block;margin-bottom:.5rem;color:#9ca3af;">Motif du rejet</label>
                                    <textarea name="rejection_reason" required class="form-control" style="background:#374151;border:1px solid #4b5563;color:white;width:100%;padding:.5rem;border-radius:4px;" rows="3"></textarea>
                                </div>
                                <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                                    <button type="button" class="btn btn-ghost" onclick="closeRejectModal('{{ $doc->id }}')">Annuler</button>
                                    <button type="submit" class="btn btn-sm btn-danger">Rejeter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div style="padding:.6rem;border-radius:.4rem;background:rgba({{ $isUrgent ? '239,68,68' : ($isWarning ? '245,158,11' : '56,189,248') }},0.1);border-left:3px solid {{ $isUrgent ? 'var(--danger)' : ($isWarning ? 'var(--warning)' : 'var(--info)') }};">
                        <div style="display:flex;justify-content:space-between;align-items:start;gap:.75rem;margin-bottom:.3rem;">
                            <div>
                                <div style="font-weight:600;font-size:.85rem;">{{ $doc->name }}</div>
                                <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
                                    {{ $doc->code ?: 'Sans code' }} | Par {{ $doc->creator->name ?? 'Inconnu' }} | {{ $doc->revision }}
                                </div>
                            </div>
                            <span class="badge {{ $badgeClass }}" style="font-size:.7rem;white-space:nowrap;">
                                @if($isUrgent)
                                    URGENT
                                @elseif($isWarning)
                                    WARNING
                                @else
                                    EN ATTENTE
                                @endif
                            </span>
                        </div>
                        <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                            @if($doc->current_role === 'validator' && $doc->status === 'in_validation')
                                <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Telecharger</a>
                                <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openConfirmModal('{{ $doc->id }}')">Valider</button>
                                <button type="button" class="btn btn-sm btn-danger" style="font-size:.72rem;" onclick="openRejectModal('{{ $doc->id }}')">Rejeter</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="color:var(--muted);padding:1rem;text-align:center;">
                        <i class="fas fa-check-circle fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                        Aucun document en attente de validation.
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    <div class="card" style="min-height: 180px;">
        <div class="card-header">
            <div>
                <div class="card-title">
                    @if($filter === 'processed')
                        <i class="fas fa-check-circle" style="color:#4ade80;margin-right:.5rem;"></i>Documents valides
                    @elseif($filter === 'rejected')
                        <i class="fas fa-times-circle" style="color:#f87171;margin-right:.5rem;"></i>Documents rejetes
                    @else
                        <i class="fas fa-list" style="color:#38bdf8;margin-right:.5rem;"></i>Tous les documents
                    @endif
                </div>
                <div class="card-sub">
                    @if($filter === 'processed')
                        Documents que vous avez signs comme validateur
                    @elseif($filter === 'rejected')
                        Documents que vous avez retournes au createur
                    @else
                        Liste complete des documents
                    @endif
                </div>
            </div>
            @php
                $badgeClass = match($filter) {
                    'processed' => 'badge-success',
                    'rejected' => 'badge-danger',
                    default => 'badge-info'
                };
            @endphp
            <span class="badge {{ $badgeClass }}" style="font-size:.8rem;padding:.3rem .7rem;">
                {{ $documents->total() }} document(s)
            </span>
        </div>

        @if($documents->isEmpty())
            <div style="color:var(--muted);padding:1rem;text-align:center;">
                <i class="fas fa-check-circle fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                Aucun document.
            </div>
        @endif
    </div>

    <div class="card" id="notifications" style="min-height: 150px;">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-bell" style="color:#38bdf8;margin-right:.5rem;"></i>Notifications</div>
                <div class="card-sub">Vos dernieres alertes documentaires.</div>
            </div>
        </div>
        @forelse($notifications as $notification)
            <div style="padding:.55rem 0;border-bottom:1px solid rgba(31,41,55,0.8);">
                <div style="font-size:.78rem;">{{ $notification->data['message'] ?? ($notification->data['type'] ?? 'Notification') }}</div>
                <div style="font-size:.72rem;color:var(--muted);">{{ $notification->created_at->format('d/m/Y H:i') }}</div>
            </div>
        @empty
            <div style="color:var(--muted);padding:1rem;text-align:center;">
                <i class="fas fa-bell-slash fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                Aucune notification pour le moment.
            </div>
        @endforelse
    </div>
</div>



<script>
function toggleReject(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function openConfirmModal(id) {
    document.getElementById('confirm-modal-' + id).style.display = 'block';
}

function closeConfirmModal(id) {
    document.getElementById('confirm-modal-' + id).style.display = 'none';
}

function openValidateModal(id) {
    document.getElementById('validate-modal-' + id).style.display = 'block';
}

function closeValidateModal(id) {
    document.getElementById('validate-modal-' + id).style.display = 'none';
}

function openRejectModal(id) {
    document.getElementById('reject-modal-' + id).style.display = 'block';
}

function closeRejectModal(id) {
    document.getElementById('reject-modal-' + id).style.display = 'none';
}

function downloadDocument(id) {
    window.open('/documents/' + id + '/download', '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>
@endsection