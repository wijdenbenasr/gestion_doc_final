@extends('layouts.app')

@section('title', 'Documents - ' . ucfirst($status ?? 'Tous'))

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">
                Documents
                @if($status)
                    @switch($status)
                        @case('created')
                            créés
                            @break
                        @case('in_validation')
                            en validation
                            @break
                        @case('archived')
                            finalisés
                            @break
                        @case('rejected')
                            rejetés
                            @break
                        @default
                            {{ $status }}
                    @endswitch
                @endif
            </div>
            <div class="card-sub">
                @if($status)
                    Liste filtrée des documents selon le statut sélectionné.
                @else
                    Tous les documents du système.
                @endif
                Période : {{ $range === 'week' ? '7 jours' : ($range === 'month' ? '30 jours' : '1 an') }}
            </div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Retour au dashboard</a>
            <form method="GET" style="display:flex;align-items:center;gap:.4rem;margin:0;">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="range" onchange="this.form.submit()">
                    <option value="week"  @selected($range==='week')>7 jours</option>
                    <option value="month" @selected($range==='month')>30 jours</option>
                    <option value="year"  @selected($range==='year')>1 an</option>
                </select>
            </form>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>Nom</th>
                <th>Code</th>
                <th>Type</th>
                <th>AIO</th>
                <th>Ligne</th>
                <th>Phase</th>
                <th>Rev.</th>
                <th>Role actuel</th>
                <th>Statut</th>
                <th>Créé le</th>
                <th>Validé le</th>
                <th>Approuvé le</th>
                <th>Rejeté le</th>
                <th>Signe</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($documents as $doc)
                <tr>
                    <td style="font-weight:500;max-width:150px;">
                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $doc->name }}">{{ $doc->name }}</div>
                        <div style="font-size:.67rem;color:var(--muted);">{{ $doc->creator->name ?? '' }}</div>
                    </td>
                    <td style="font-family:monospace;font-size:.72rem;color:var(--accent);">{{ $doc->code ?? '-' }}</td>
                    <td style="font-size:.7rem;max-width:110px;" title="{{ $doc->type_libelle }}">{{ \Illuminate\Support\Str::limit($doc->type_libelle, 20) }}</td>
                    <td><span class="badge badge-info">{{ \App\Models\Document::AIOS[$doc->aio] ?? $doc->aio }}</span></td>
                    <td style="font-size:.78rem;">{{ $doc->ligne }}</td>
                    <td style="font-size:.72rem;">{{ $doc->phase_libelle }}</td>
                    <td style="font-family:monospace;font-size:.73rem;">{{ $doc->revision }}</td>
                    <td>
                        @if($doc->current_role)
                            <span class="badge badge-muted">{{ $doc->current_role }}</span>
                        @else
                            <span style="color:var(--muted);">-</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $statusConfig = [
                                'archived' => ['badge-success', 'Finalise'],
                                'rejected' => ['badge-danger', 'Rejete'],
                                'pending_codification' => ['badge-warning', 'Codification'],
                                'in_validation' => ['badge-info', 'Validation'],
                                'draft' => ['badge-muted', 'Brouillon'],
                            ];
                            $status = $statusConfig[$doc->status] ?? ['badge-muted', $doc->status];
                        @endphp
                        <span class="badge {{ $status[0] }}">{{ $status[1] }}</span>
                    </td>
                    <td style="font-size:.7rem;">
                        {{ $doc->created_at->format('d/m/Y H:i') }}<br>
                        <span style="color:var(--muted);">{{ $doc->creator->name ?? '-' }}</span>
                    </td>
                    <td style="font-size:.7rem;">
                        @php
                            $validatorSig = $doc->signatures->where('role', 'validator')->first();
                        @endphp
                        @if($validatorSig)
                            {{ $validatorSig->signed_at->format('d/m/Y H:i') }}<br>
                            <span style="color:var(--muted);">{{ $validatorSig->user->name ?? '-' }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td style="font-size:.7rem;">
                        @php
                            $approverSig = $doc->signatures->where('role', 'approver')->first();
                        @endphp
                        @if($approverSig)
                            {{ $approverSig->signed_at->format('d/m/Y H:i') }}<br>
                            <span style="color:var(--muted);">{{ $approverSig->user->name ?? '-' }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td style="font-size:.7rem;">
                        @php
                            $rejectTrans = $doc->transmissions->where('action', 'reject')->first();
                        @endphp
                        @if($rejectTrans)
                            {{ $rejectTrans->created_at->format('d/m/Y H:i') }}<br>
                            <span style="color:var(--muted);">{{ $rejectTrans->sender->name ?? '-' }}</span>
                        @elseif($doc->status === 'rejected')
                            {{ $doc->updated_at->format('d/m/Y H:i') }}<br>
                            <span style="color:var(--muted);">-</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($doc->is_fully_signed)
                            <span class="badge badge-success">Oui</span>
                        @else
                            <span style="color:var(--muted);font-size:.72rem;">Non</span>
                        @endif
                    </td>
                    <td>
    <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
        <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm">Télécharger</a>

        @if($doc->status === 'validation_admin' && $doc->current_role === 'admin')
            <button type="button" class="btn btn-sm"
                    style="border-color:rgba(34,197,94,0.5);color:#4ade80;"
                    onclick="openValidateModal('{{ $doc->id }}')">
                <i class="fas fa-check"></i> Valider
            </button>
            <button type="button" class="btn btn-sm"
                    style="border-color:rgba(239,68,68,0.5);color:#f87171;"
                    onclick="openRejectModal('{{ $doc->id }}')">
                <i class="fas fa-times"></i> Rejeter
            </button>
        @endif

        @if($doc->status === 'signing_admin' && $doc->current_role === 'admin')
            <button type="button" class="btn btn-sm"
                    style="border-color:rgba(168,85,247,0.5);color:#c084fc;"
                    onclick="openSignModal('{{ $doc->id }}')">
                <i class="fas fa-signature"></i> Signer
            </button>
        @endif
    </div>
</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align:center;color:var(--muted);padding:1.5rem;">
                        @if($status)
                            Aucun document
                            @switch($status)
                                @case('created')
                                    créé
                                    @break
                                @case('in_validation')
                                    en validation
                                    @break
                                @case('archived')
                                    finalisé
                                    @break
                                @case('rejected')
                                    rejeté
                                    @break
                                @default
                                    avec ce statut
                            @endswitch
                            sur cette période.
                        @else
                            Aucun document sur cette période.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $documents->links() }}</div>
</div>

<!-- Modals de rejet -->
@foreach($documents as $doc)
@if($doc->status === 'in_validation' && $doc->current_role === 'admin')
<div id="rejetModal{{ $doc->id }}" class="modal" style="display:none;">
    <div class="modal-content" style="background:#1a2035;color:white;border:1px solid rgba(255,255,255,0.1);border-radius:10px;">
        <div class="modal-header">
            <h3 style="margin:0;color:white;">Rejeter le document</h3>
            <button type="button" class="modal-close" onclick="closeRejectModal('{{ $doc->id }}')">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.workflow.reject', $doc) }}">
            @csrf
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" style="color:white;">Motif du rejet *</label>
                    <textarea name="message" class="form-control" style="background:#0f172a;color:white;border:1px solid rgba(255,255,255,0.1);border-radius:.5rem;" rows="4" placeholder="Expliquez la raison du rejet..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color:white;">Deadline de correction</label>
                    <input type="date" name="deadline" class="form-control" style="background:#0f172a;color:white;border:1px solid rgba(255,255,255,0.1);border-radius:.5rem;">
                    <small style="color:#9ca3af;">Date limite pour que le créateur corrige et renvoie le document</small>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="closeRejectModal('{{ $doc->id }}')">Annuler</button>
                    <button type="submit" class="btn btn-danger">Confirmer le rejet</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

<!-- Modals de signature admin -->
@foreach($documents as $doc)
@if($doc->status === 'signing_admin' && $doc->current_role === 'admin')
<div id="signModal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h4 style="margin:0;">✍️ Signer et finaliser le document</h4>
            <button type="button" class="btn-close btn-close-white" onclick="closeSignModal('{{ $doc->id }}')"></button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('admin.workflow.sign', $doc) }}">
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
                    <button type="submit" class="btn btn-primary">Signer et finaliser</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach

<!-- Modals de validation admin -->
@foreach($documents as $doc)
@if($doc->status === 'validation_admin' && $doc->current_role === 'admin')
<div id="validateModal-{{ $doc->id }}" style="display:none;position:fixed;z-index:1060;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
    <div style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:450px;border:1px solid rgba(255,255,255,0.1);">
        <h4 style="margin-bottom:1rem;color:white;">Valider le document</h4>
        <p style="color:#9ca3af;margin-bottom:1.5rem;">Valider <strong style="color:white;">{{ $doc->name }}</strong> pour conversion PDF ?</p>
        <form method="POST" action="{{ route('admin.workflow.validate', $doc) }}">
            @csrf
            <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('validateModal-{{ $doc->id }}').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-sm" style="background:rgba(34,197,94,0.2);border:1px solid rgba(34,197,94,0.5);color:#4ade80;">Confirmer la validation</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

<script>
console.log('Admin documents scripts loaded');

function openSignModal(docId) {
    console.log('openSignModal called with id:', docId);
    var modal = document.getElementById('signModal-' + docId);
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.log('Modal not found: signModal-' + docId);
    }
}

function closeSignModal(docId) {
    var modal = document.getElementById('signModal-' + docId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function openRejectModal(docId) {
    var modal = document.getElementById('rejetModal' + docId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeRejectModal(docId) {
    var modal = document.getElementById('rejetModal' + docId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function openValidateModal(id) {
    document.getElementById('validateModal-' + id).style.display = 'block';
}

function downloadDocument(id) {
    window.open('/documents/' + id + '/download', '_blank');
}
</script>
@endsection
