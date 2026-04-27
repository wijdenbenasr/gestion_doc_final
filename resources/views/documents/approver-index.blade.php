@extends('layouts.app')

@section('title', 'Documents a approuver')

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
    <a href="{{ route('workflow.approver.index') }}" class="stat-card {{ !$filter || $filter === 'pending' ? 'active' : '' }}">
        <div class="stat-label">En attente</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $stats['pending'] }}</div>
        <div class="stat-meta">Documents a approuver</div>
    </a>
    <a href="{{ route('workflow.approver.index', ['filter' => 'processed']) }}" class="stat-card {{ $filter === 'processed' ? 'active' : '' }}">
        <div class="stat-label">Approuvés</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['processed'] }}</div>
        <div class="stat-meta">Historique personnel</div>
    </a>
    <a href="{{ route('workflow.approver.index', ['filter' => 'rejected']) }}" class="stat-card {{ $filter === 'rejected' ? 'active' : '' }}">
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

<div class="card" id="recent-alerts" style="margin-bottom: 1rem;">
    <div class="card-header">
        <div>
            <div class="card-title"><i class="fas fa-bell" style="color:#f59e0b;margin-right:.5rem;"></i>Alertes prioritaires</div>
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
                <div id="sign-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
                    <div class="modal-content" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
                        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                            <h4 style="margin:0;">✍️ Signer et envoyer le document</h4>
                            <button type="button" class="btn-close btn-close-white" onclick="closeSignModal('{{ $doc->id }}')"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.approver.sign', $doc) }}">
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
                                    <button type="button" class="btn btn-secondary" onclick="closeSignModal('{{ $doc->id }}')">Annuler</button>
                                    <button type="submit" class="btn btn-primary">Signer et envoyer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div id="rejet-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
                    <div class="modal-content" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;border:1px solid rgba(255,255,255,0.1);">
                        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                            <h4 style="margin:0;color:white;">Rejeter le document</h4>
                            <button type="button" class="btn-close btn-close-white" onclick="closeRejectModal('{{ $doc->id }}')"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST" action="{{ route('workflow.approver.reject', $doc) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" style="color:#9ca3af;">Motif du rejet *</label>
                                    <textarea name="message" class="form-control" style="background:#0f172a;color:white;border:1px solid rgba(255,255,255,0.1);border-radius:.5rem;" rows="4" placeholder="Expliquez la raison du rejet..." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" style="color:#9ca3af;">Deadline de correction</label>
                                    <input type="date" name="deadline" class="form-control" style="background:#0f172a;color:white;border:1px solid rgba(255,255,255,0.1);border-radius:.5rem;">
                                </div>
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal('{{ $doc->id }}')">Annuler</button>
                                    <button type="submit" class="btn btn-danger">Confirmer le rejet</button>
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
                                {{ $doc->code ?: 'Sans code' }} | Par {{ $doc->creator->name ?? 'Inconnu' }} | v{{ $doc->revision }}
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
                        <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openSignModal('{{ $doc->id }}')">Signer</button>
                    </div>
                </div>
            @empty
                <div style="color:var(--muted);padding:1.25rem;text-align:center;">
                    <i class="fas fa-check-circle fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                    Aucun document pret pour signature.
                </div>
            @endforelse
        </div>
    @endif
</div>

<div class="card" style="margin-bottom: 1rem;">
    <div class="card-header">
        <div>
            <div class="card-title">
                @if($filter === 'processed')
                    <i class="fas fa-check-circle" style="color:#4ade80;margin-right:.5rem;"></i>Documents approuves
                @elseif($filter === 'rejected')
                    <i class="fas fa-times-circle" style="color:#f87171;margin-right:.5rem;"></i>Documents rejetes
                @else
                    <i class="fas fa-file-signature" style="color:#f59e0b;margin-right:.5rem;"></i>Documents a approuver
                @endif
            </div>
            <div class="card-sub">
                @if($filter === 'processed')
                    Documents que vous avez approuves
                @elseif($filter === 'rejected')
                    Documents que vous avez retournes au createur
                @else
                    Documents valides par le validateur et en attente de votre approbation
                @endif
            </div>
        </div>
        @php
            $badgeClass = $documents->total() > 0 
                ? ($filter === 'rejected' ? 'badge-danger' : 'badge-warning')
                : 'badge-muted';
        @endphp
        <span class="badge {{ $badgeClass }}" style="font-size:.8rem;padding:.3rem .7rem;">
            {{ $documents->total() }} document(s)
        </span>
    </div>

    @if($documents->isEmpty())
        <div style="text-align:center;padding:2rem;color:var(--muted);">
            <i class="fas fa-{{ $filter === 'processed' ? 'check-circle' : ($filter === 'rejected' ? 'times-circle' : 'file-signature') }} fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
            <div>
                @if($filter === 'processed')
                    Aucun document approuve.
                @elseif($filter === 'rejected')
                    Aucun document rejete.
                @else
                    Aucun document en attente d approbation.
                @endif
            </div>
        </div>
    @else
        <div style="overflow-x:auto;margin-top:.75rem;position:relative;">
            <table style="position:relative;">
                <thead>
                <tr>
                    <th>Nom</th><th>Code</th><th>Type</th><th>AIO</th>
                    <th>Ligne</th><th>Phase</th><th>Rev.</th>
                    <th>Createur</th><th>Deadline</th><th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($documents as $doc)
                    <tr>
                        <td style="font-weight:500;max-width:160px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $doc->name }}">
                                {{ $doc->name }}
                            </div>
                        </td>
<td>
                                @if($doc->code)
                                    <span class="badge bg-secondary">{{ $doc->code }}</span>
                                @else
                                    <span class="badge bg-secondary" style="opacity:0.5;">Non codifie</span>
                                @endif
                            </td>
                            <td>
                                <span title="{{ $doc->type_libelle }}">{{ Str::limit($doc->type_libelle, 20) }}</span>
                            </td>
                        <td><span class="badge badge-info">{{ \App\Models\Document::AIOS[$doc->aio] ?? $doc->aio }}</span></td>
                        <td>{{ $doc->ligne }}</td>
                        <td style="font-size:.72rem;">{{ $doc->phase_libelle }}</td>
                        <td style="font-family:monospace;font-size:.75rem;">v{{ $doc->revision }}</td>
                        <td>{{ $doc->creator->name ?? '—' }} {{ $doc->creator->prenom ?? '' }}</td>
                        <td style="font-size:.72rem;">
                            @if($doc->deadline)
                                <span style="{{ $doc->deadline->isPast() ? 'color:var(--danger)' : '' }}">
                                    {{ $doc->deadline->format('d/m/Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('documents.download', $doc) }}"><i class="fas fa-download me-2"></i>Telecharger</a></li>
                                    <li><button type="button" class="dropdown-item" style="color:#4ade80;cursor:pointer;" onclick="openTableSignModal('{{ $doc->id }}')"><i class="fas fa-signature me-2"></i>Signer</button></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $documents->links() }}</div>
    @endif
</div>


<!-- Modal de signature (alertes) -->
@if($showAlertes)
@foreach($alertes as $doc)
<div id="sign-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h4 style="margin:0;">✍️ Signer et envoyer le document</h4>
            <button type="button" class="btn-close btn-close-white" onclick="closeSignModal('{{ $doc->id }}')"></button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.approver.sign', $doc) }}">
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
                    <button type="button" class="btn btn-secondary" onclick="closeSignModal('{{ $doc->id }}')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Signer et envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif

<!-- Modal de signature (table) -->
@foreach($documents as $doc)
<div id="table-sign-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h4 style="margin:0;">✍️ Signer et envoyer le document</h4>
            <button type="button" class="btn-close btn-close-white" onclick="closeTableSignModal('{{ $doc->id }}')"></button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.approver.sign', $doc) }}">
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
                    <button type="button" class="btn btn-secondary" onclick="closeTableSignModal('{{ $doc->id }}')">Annuler</button>
                    <button type="submit" class="btn btn-primary">Signer et envoyer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<div class="cards-row" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
    <div class="card" style="min-height: 150px;">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-history" style="color:#38bdf8;margin-right:.5rem;"></i>Historique recent</div>
                <div class="card-sub">Derniers documents que vous avez deja approuves.</div>
            </div>
        </div>
        @forelse($processedDocuments as $doc)
            <div style="padding:.55rem 0;border-bottom:1px solid rgba(31,41,55,0.8);">
                <div style="font-weight:600;">{{ $doc->name }}</div>
                <div style="font-size:.74rem;color:var(--muted);">{{ $doc->code ?: 'Sans code' }} | v{{ $doc->revision }}</div>
            </div>
        @empty
            <div style="color:var(--muted);padding:1rem;text-align:center;">
                <i class="fas fa-history fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                Aucun document traite pour le moment.
            </div>
        @endforelse
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
function openSignModal(id) {
    document.getElementById('sign-modal-' + id).style.display = 'block';
}
function closeSignModal(id) {
    document.getElementById('sign-modal-' + id).style.display = 'none';
}
function openTableSignModal(id) {
    document.getElementById('table-sign-modal-' + id).style.display = 'block';
}
function closeTableSignModal(id) {
    document.getElementById('table-sign-modal-' + id).style.display = 'none';
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