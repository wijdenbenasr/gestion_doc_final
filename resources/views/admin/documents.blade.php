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
                        @case('finalized')
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
                    <td style="font-family:monospace;font-size:.73rem;">v{{ $doc->revision }}</td>
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
                                'finalized' => ['badge-success', 'Finalise'],
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
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm">Source</a>
                            @if($doc->status === 'in_validation' && $doc->current_role === 'admin')
                                @php
                                    $hasAdminSignature = \App\Models\DocumentSignature::where('document_id', $doc->id)->where('role', 'admin')->exists();
                                @endphp
                                @if($hasAdminSignature)
                                    <button type="button" class="btn btn-sm" onclick="openSignModal('{{ $doc->id }}')">Signer et finaliser</button>
                                @else
                                    <form method="POST" action="{{ route('admin.workflow.validate', $doc) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Valider</button>
                                    </form>
                                @endif
<button type="button" class="btn btn-ghost btn-sm btn-danger" onclick="openRejectModal('{{ $doc->id }}')">Rejeter</button>
                            </div>
                                <a href="{{ route('admin.documents.export.pdf', $doc) }}" class="btn btn-ghost btn-sm">PDF</a>
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
                                @case('finalized')
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
@if($doc->status === 'in_validation' && $doc->current_role === 'admin')
@php
    $hasAdminSignature = \App\Models\DocumentSignature::where('document_id', $doc->id)->where('role', 'admin')->exists();
@endphp
@if($hasAdminSignature)
<div id="signModal-{{ $doc->id }}" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Finaliser le document</h3>
            <button type="button" class="modal-close" onclick="closeSignModal('{{ $doc->id }}')">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.workflow.sign', $doc) }}">
            @csrf
            <div class="modal-body">
                <p>Vous allez signer et finaliser ce document : <strong>{{ $doc->name }}</strong></p>
                <div style="margin:1rem 0;">
                    <label for="signature-{{ $doc->id }}">Votre signature :</label>
                    <input type="text" id="signature-{{ $doc->id }}" name="signature" required style="width:100%;padding:.5rem;margin:.5rem 0;border:1px solid var(--border);border-radius:.25rem;" placeholder="Tapez votre nom complet">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeSignModal('{{ $doc->id }}')">Annuler</button>
                <button type="submit" class="btn btn-primary">Signer et finaliser</button>
            </div>
        </form>
    </div>
</div>
@endif
@endif
@endforeach

<script>
function openSignModal(docId) {
    document.getElementById('signModal-' + docId).style.display = 'block';
}

function closeSignModal(docId) {
    document.getElementById('signModal-' + docId).style.display = 'none';
}

function openRejectModal(docId) {
    document.getElementById('rejetModal' + docId).style.display = 'block';
}

function closeRejectModal(docId) {
    document.getElementById('rejetModal' + docId).style.display = 'none';
}

function downloadDocument(id) {
    window.open('/documents/' + id + '/download', '_blank');
}
</script>
@endsection
