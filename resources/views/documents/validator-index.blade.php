@extends('layouts.app')

@section('title', 'Documents a valider')

@section('content')
<div class="cards-row" style="grid-template-columns: repeat(5, 1fr); margin-bottom: 1rem;">
    <div class="stat-card">
        <div class="stat-label">En attente de validation</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $stats['en_attente_validation'] }}</div>
        <div class="stat-meta">Documents a valider ou rejeter</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">En attente de signature</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $stats['en_attente_signature'] }}</div>
        <div class="stat-meta">Documents a signer</div>
    </div>
    <a href="{{ route('workflow.validator.index', ['filter' => 'processed']) }}" class="stat-card {{ $filter === 'processed' ? 'active' : '' }}">
        <div class="stat-label">Valides</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['processed'] }}</div>
        <div class="stat-meta">Historique personnel</div>
    </a>
    <a href="{{ route('workflow.validator.index', ['filter' => 'rejected']) }}" class="stat-card {{ $filter === 'rejected' ? 'active' : '' }}">
        <div class="stat-label">Rejets</div>
        <div class="stat-value" style="color:#f87171;">{{ $stats['rejected'] }}</div>
        <div class="stat-meta">Documents retournes au createur</div>
    </a>
    <a href="#notifications" class="stat-card">
        <div class="stat-label">Notifications</div>
        <div class="stat-value" style="color:#a78bfa;">{{ $stats['notifications'] }}</div>
        <div class="stat-meta">Non lues</div>
    </a>
</div>

<div class="cards-row" style="grid-template-columns: 1fr 1fr; gap: 1rem; flex-grow: 1; margin-bottom: 1rem;">
    <div class="card" id="recent-alerts" style="min-height: 180px;">
        <div class="card-header">
            @php
                $showAlertes = isset($alertes) && $alertes->count() > 0;
            @endphp
            <div>
                <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;margin-right:.5rem;"></i>Alertes prioritaires</div>
                <div class="card-sub">Documents a traiter en priorite.</div>
            </div>
            <span class="badge badge-danger" style="font-size:.8rem;padding:.3rem .7rem;">
                {{ $alertes->count() ?? 0 }} document(s)
            </span>
        </div>

        @if($showAlertes)
            <div style="display:grid;gap:.4rem;">
                @foreach($alertes as $doc)
                    @php
                        $isUrgent = $doc->deadline && $doc->deadline->isPast();
                        $isWarning = !$isUrgent && $doc->deadline && $doc->deadline->isBefore(now()->addDays(2));
                        $badgeClass = $isUrgent ? 'badge-danger' : ($isWarning ? 'badge-warning' : 'badge-info');
                        $needsSignature = in_array($doc->status, ['signing_validator', 'SIGNATURE_VALIDATEUR']);
                    @endphp

                    @if($needsSignature)
                    <div id="validate-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 0;">
                        <div style="background:#0f172a;border:1px solid #3b82f6;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:20px auto;box-shadow:0 25px 60px rgba(59,130,246,0.15);max-height:calc(100vh - 40px);overflow-y:auto;position:relative;">
                            <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.validator.sign', $doc) }}">
                                @csrf
                                <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
                                    <div style="width:42px;height:42px;background:rgba(59,130,246,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:bold;color:#3b82f6;">S</div>
                                    <div>
                                        <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Signer et envoyer le document</h5>
                                        <p style="color:#64748b;margin:0;font-size:0.8rem;">Téléversez le document signé pour continuer le workflow</p>
                                    </div>
                                    <button type="button" onclick="closeValidateModal('{{ $doc->id }}')" style="margin-left:auto;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:6px;">X</button>
                                </div>
                                <div style="background:#1e293b;border-radius:10px;padding:12px 16px;margin-bottom:1.2rem;">
                                    <p style="color:#94a3b8;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;margin:0 0 4px;">DOCUMENT</p>
                                    <p style="color:white;font-weight:600;margin:0;">{{ $doc->name }}</p>
                                    <p style="color:#3b82f6;font-size:0.82rem;margin:4px 0 0;">Code : {{ $doc->code }}</p>
                                </div>
                                <div style="margin-bottom:1.2rem;">
                                    <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                                        TÉLÉVERSER LE DOCUMENT SIGNÉ <span style="color:#ef4444;">*</span>
                                    </label>
                                    <label for="signedFile{{ $doc->id }}" style="display:flex;align-items:center;gap:12px;background:#1e293b;border:2px dashed #334155;border-radius:10px;padding:16px;cursor:pointer;transition:all 0.2s;"
                                           onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#334155'">
                                        <div style="width:36px;height:36px;background:rgba(59,130,246,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;color:#3b82f6;">+</div>
                                        <div>
                                            <p style="color:white;font-weight:500;margin:0;font-size:0.9rem;" id="signedFileName{{ $doc->id }}">Choisir un fichier PDF</p>
                                            <p style="color:#64748b;font-size:0.78rem;margin:2px 0 0;">Format accepté : PDF uniquement</p>
                                        </div>
                                    </label>
                                    <input type="file" id="signedFile{{ $doc->id }}" name="document_signe" accept=".pdf" required style="display:none;"
                                           onchange="document.getElementById('signedFileName{{ $doc->id }}').textContent = this.files[0]?.name || 'Choisir un fichier PDF'">
                                </div>
                                <div style="margin-bottom:1.5rem;">
                                    <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                                        COMMENTAIRE <span style="color:#64748b;font-size:0.75rem;">(optionnel)</span>
                                    </label>
                                    <textarea name="commentaire" rows="3"
                                        placeholder="Ajouter un commentaire..."
                                        style="width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;padding:12px;font-size:0.9rem;resize:vertical;outline:none;box-sizing:border-box;transition:border-color 0.2s;"
                                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#334155'"></textarea>
                                </div>
                                <div style="display:flex;gap:12px;justify-content:flex-end;">
                                    <button type="button" onclick="closeValidateModal('{{ $doc->id }}')"
                                        style="padding:10px 24px;background:#1e293b;color:#94a3b8;border:1px solid #334155;border-radius:10px;cursor:pointer;font-size:0.9rem;transition:all 0.2s;"
                                        onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#1e293b'">
                                        Annuler
                                    </button>
                                    <button type="submit"
                                        style="padding:10px 24px;background:#3b82f6;color:white;border:none;border-radius:10px;cursor:pointer;font-size:0.9rem;font-weight:600;transition:all 0.2s;"
                                        onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                                        Signer et envoyer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

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
                                @elseif($needsSignature)
                                    PRET POUR SIGNATURE
                                @else
                                    EN ATTENTE
                                @endif
                            </span>
                        </div>
                        <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Telecharger</a>
                            @if($needsSignature)
                                <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openValidateModal('{{ $doc->id }}')">Signer</button>
                            @else
                                <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openConfirmModal('{{ $doc->id }}')">Valider</button>
                                <button type="button" class="btn btn-sm btn-danger" style="font-size:.72rem;" onclick="openRejectModal('{{ route('workflow.validator.reject', $doc) }}')">Rejeter</button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card" style="min-height: 180px;">
        <div class="card-header">
            @php
                $showDocsAVerifier = isset($documentsAVerifier) && $documentsAVerifier->count() > 0;
            @endphp
            <div>
                <div class="card-title">
                    <i class="fas fa-file-search" style="color:#f59e0b;margin-right:.5rem;"></i>Documents a verifier
                </div>
                <div class="card-sub">
                    Documents en attente de votre validation (Valider/Rejeter)
                </div>
            </div>
            <span class="badge badge-warning" style="font-size:.8rem;padding:.3rem .7rem;">
                {{ $documentsAVerifier->count() ?? 0 }} document(s)
            </span>
        </div>

        @if($showDocsAVerifier)
            <div style="display:grid;gap:.4rem;">
                @foreach($documentsAVerifier as $doc)
                    @php
                        $isUrgent = $doc->deadline && $doc->deadline->isPast();
                        $isWarning = !$isUrgent && $doc->deadline && $doc->deadline->isBefore(now()->addDays(2));
                        $badgeClass = $isUrgent ? 'badge-danger' : ($isWarning ? 'badge-warning' : 'badge-info');
                    @endphp
                    <div id="confirm-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 0;">
                        <div class="modal-content" style="background:#1f2937;margin:20px auto;padding:1.5rem;border-radius:8px;max-width:400px;max-height:calc(100vh - 40px);overflow-y:auto;position:relative;">
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
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Telecharger</a>
                            <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openConfirmModal('{{ $doc->id }}')">Valider</button>
                            <button type="button" class="btn btn-sm btn-danger" style="font-size:.72rem;" onclick="openRejectModal('{{ route('workflow.validator.reject', $doc) }}')">Rejeter</button>
                        </div>
                    </div>
                @endforeach
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
        @else
            <div style="display:grid;gap:.4rem;">
                @foreach($documents as $doc)
                    @php
                        $statusBadge = match($doc->status) {
                            'in_validation' => ['class' => 'badge-warning', 'label' => 'EN VALIDATION'],
                            'signing_validator' => ['class' => 'badge-info', 'label' => 'SIGNATURE'],
                            'EN_VALIDATION' => ['class' => 'badge-warning', 'label' => 'EN VALIDATION'],
                            'SIGNATURE_VALIDATEUR' => ['class' => 'badge-info', 'label' => 'SIGNATURE'],
                            'approved' => ['class' => 'badge-success', 'label' => 'APPROUVE'],
                            'archived' => ['class' => 'badge-secondary', 'label' => 'FINALISÉ'],
                            'EN_MODIFICATION' => ['class' => 'badge-danger', 'label' => 'EN MODIFICATION'],
                            default => ['class' => 'badge-info', 'label' => strtoupper($doc->status)],
                        };
                    @endphp
                    <div style="padding:.6rem;border-radius:.4rem;background:rgba(255,255,255,0.03);border-left:3px solid {{ $doc->status === 'archived' ? 'var(--muted)' : 'var(--info)' }};">
                        <div style="display:flex;justify-content:space-between;align-items:start;gap:.75rem;margin-bottom:.3rem;">
                            <div>
                                <div style="font-weight:600;font-size:.85rem;">{{ $doc->name }}</div>
                                <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
                                    {{ $doc->code ?: 'Sans code' }} | Par {{ $doc->creator->name ?? 'Inconnu' }} | {{ $doc->revision }}
                                </div>
                            </div>
                            <span class="badge {{ $statusBadge['class'] }}" style="font-size:.7rem;white-space:nowrap;">
                                {{ $statusBadge['label'] }}
                            </span>
                        </div>
                        <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Telecharger</a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="padding:1rem;text-align:center;">
                {{ $documents->links() }}
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



<!-- Unified Reject Modal -->
<div id="rejectModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#0f172a;border:1px solid #ef4444;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:auto;box-shadow:0 25px 60px rgba(239,68,68,0.2);">

    <!-- Header -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
      <div style="width:42px;height:42px;background:rgba(239,68,68,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;"></div>
      <div>
        <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Rejeter le document</h5>
        <p style="color:#64748b;margin:0;font-size:0.8rem;">Le document sera renvoyé au créateur pour correction</p>
      </div>
      <button onclick="closeRejectModal()" style="margin-left:auto;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:6px;">X</button>
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
         Confirmer le rejet
        </button>
      </div>
    </form>
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



