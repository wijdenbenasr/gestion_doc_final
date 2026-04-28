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
                            crs
                            @break
                        @case('in_validation')
                            en validation
                            @break
                        @case('archived')
                            finalisés
                            @break
                        @case('rejected')
                            rejets
                            @break
                        @default
                            {{ $status }}
                    @endswitch
                @endif
            </div>
            <div class="card-sub">
                @if($status)
                    Liste filtre des documents selon le statut slectionn.
                @else
                    Tous les documents du système.
                @endif
                Priode : {{ $range === 'week' ? '7 jours' : ($range === 'month' ? '30 jours' : '1 an') }}
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
                <th>Cr le</th>
                <th>Valid le</th>
                <th>Approuv le</th>
                <th>Rejet le</th>
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
                            $status = $statusConfig[$doc->status]  ['badge-muted', $doc->status];
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
                    onclick="openRejectModal('{{ route('admin.workflow.reject', $doc) }}')">
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
                                    cr
                                    @break
                                @case('in_validation')
                                    en validation
                                    @break
                                @case('archived')
                                    finalis
                                    @break
                                @case('rejected')
                                    rejet
                                    @break
                                @default
                                    avec ce statut
                            @endswitch
                            sur cette priode.
                        @else
                            Aucun document sur cette priode.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
<div class="pagination">{{ $documents->links() }}</div>
</div>

<!-- Unified Reject Modal -->
<div id="rejectModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#0f172a;border:1px solid #ef4444;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:auto;box-shadow:0 25px 60px rgba(239,68,68,0.2);">

    <!-- Header -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
      <div style="width:42px;height:42px;background:rgba(239,68,68,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">Œ</div>
      <div>
        <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Rejeter le document</h5>
        <p style="color:#64748b;margin:0;font-size:0.8rem;">Le document sera renvoyé au créateur pour correction</p>
      </div>
      <button onclick="closeRejectModal()" style="margin-left:auto;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:6px;">✕</button>
    </div>

    <form id="rejectForm" method="POST">
      @csrf

      <!-- Motif -->
      <div style="margin-bottom:1.2rem;">
        <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:6px;">MOTIF DU REJET <span style="color:#ef4444;">*</span></label>
        <textarea name="motif_rejet" required rows="4"
          placeholder="Décrivez la raison du rejet et les corrections attendues..."
          style="width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;padding:12px;font-size:0.9rem;resize:vertical;outline:none;box-sizing:border-box;transition:border-color 0.2s;"
          onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#334155'"></textarea>
      </div>

      <!-- Deadline -->
      <div style="margin-bottom:1.5rem;">
        <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:6px;">DEADLINE DE CORRECTION</label>
        <input type="date" name="deadline_correction"
          style="width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;padding:12px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s;color-scheme:dark;"
          onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#334155'">
      </div>

      <!-- Buttons -->
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <button type="button" onclick="closeRejectModal()"
          style="padding:10px 24px;background:#1e293b;color:#94a3b8;border:1px solid #334155;border-radius:10px;cursor:pointer;font-size:0.9rem;transition:all 0.2s;"
          onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#1e293b'">
          Annuler
        </button>
        <button type="submit"
          style="padding:10px 24px;background:#ef4444;color:white;border:none;border-radius:10px;cursor:pointer;font-size:0.9rem;font-weight:600;transition:all 0.2s;"
          onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
          ✕ ✓ Confirmer le rejet
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modals de signature admin -->
@foreach($documents as $doc)
@if($doc->status === 'signing_admin' && $doc->current_role === 'admin')
<div id="signModal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
        <div class="modal-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h4 style="margin:0;"> Signer et finaliser le document</h4>
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
                    <label class="form-label fw-bold"> Televerser le document signe *</label>
                    <input type="file" name="document_signe" accept=".pdf" required class="form-control" style="background:#0f172a; color:white;">
                    <small class="text-muted">Format accepte : PDF</small>
                </div>
                <div class="mb-3">
                    <label class="form-label"> Commentaire (optionnel)</label>
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

function openRejectModal(actionUrl) {
    document.getElementById('rejectForm').action = actionUrl;
    document.getElementById('rejectModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});

function openValidateModal(id) {
    document.getElementById('validateModal-' + id).style.display = 'block';
}

function downloadDocument(id) {
    window.open('/documents/' + id + '/download', '_blank');
}
</script>
@endsection




