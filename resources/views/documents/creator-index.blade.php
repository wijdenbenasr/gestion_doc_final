@extends('layouts.app')

@section('title', 'Mes documents')

@section('content')
<div class="cards-row" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 1rem;">
    <a href="{{ route('documents.creator.index', ['status' => 'draft']) }}" class="stat-card {{ $status === 'draft' ? 'active' : '' }}">
        <div class="stat-label">Brouillons</div>
        <div class="stat-value">{{ (int) ($stats->drafts ?? 0) }}</div>
        <div class="stat-meta">Documents encore modifiables</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'pending_codification']) }}" class="stat-card {{ $status === 'pending_codification' ? 'active' : '' }}">
        <div class="stat-label">Codification</div>
        <div class="stat-value" style="color:#f59e0b;">{{ (int) ($stats->pending_codification ?? 0) }}</div>
        <div class="stat-meta">En attente chez l admin</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'in_validation']) }}" class="stat-card {{ $status === 'in_validation' ? 'active' : '' }}">
        <div class="stat-label">Validation</div>
        <div class="stat-value" style="color:#38bdf8;">{{ (int) ($stats->in_validation ?? 0) }}</div>
        <div class="stat-meta">Documents en circuit</div>
    </a>
</div>
<div class="cards-row" style="grid-template-columns: repeat(3, 1fr);">
    <a href="{{ route('documents.creator.index', ['status' => 'ready_for_pdf']) }}" class="stat-card {{ $status === 'ready_for_pdf' ? 'active' : '' }}">
        <div class="stat-label">Pret pour PDF</div>
        <div class="stat-value" style="color:#10b981;">{{ (int) ($stats->ready_for_pdf ?? 0) }}</div>
        <div class="stat-meta">Documents a convertir en PDF</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'rejected']) }}" class="stat-card {{ $status === 'rejected' ? 'active' : '' }}">
        <div class="stat-label">Rejetes</div>
        <div class="stat-value" style="color:#f87171;">{{ (int) ($stats->rejected ?? 0) }}</div>
        <div class="stat-meta">A corriger et renvoyer</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'finalized']) }}" class="stat-card {{ $status === 'finalized' ? 'active' : '' }}">
        <div class="stat-label">Archives</div>
        <div class="stat-value" style="color:#4ade80;">{{ (int) ($stats->finalized ?? 0) }}</div>
        <div class="stat-meta">Documents finalises</div>
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Mes documents</div>
            <div class="card-sub">Suivez le workflow complet depuis le brouillon jusqu a l archivage final.</div>
        </div>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">Nouveau document</a>
    </div>

@if($documents->isEmpty())
        <div style="text-align:center;padding:3rem;color:var(--muted);">
            <i class="fas fa-file-alt fa-3x mb-3" style="color:rgba(255,255,255,0.15);display:block;"></i>
            <div style="font-size:.95rem;margin-bottom:.5rem;">
                @if(!$status || $status === 'draft')
                    Aucun brouillon pour le moment.
                @elseif($status === 'pending_codification')
                    Aucun document en attente de codification.
                @elseif($status === 'in_validation')
                    Aucun document en cours de validation.
                @elseif($status === 'ready_for_pdf')
                    Aucun document pret pour PDF.
                @elseif($status === 'rejected')
                    Aucun document rejecte.
                @elseif($status === 'finalized')
                    Aucun document finalise.
                @else
                    Aucun document pour le moment.
                @endif
            </div>
            <a href="{{ route('documents.create') }}" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:.5rem;">
                <i class="fas fa-plus"></i> Creer mon premier document
            </a>
        </div>
    @else
        <div style="overflow-x:auto;position:relative;">
            <table style="min-width:900px;position:relative;">
                <thead>
                <tr>
                    <th style="width:180px;">Nom</th>
                    <th style="width:100px;">Code</th>
                    <th style="width:140px;">Type</th>
                    <th style="width:80px;">AIO</th>
                    <th style="width:100px;">Ligne</th>
                    <th style="width:120px;">Phase</th>
                    <th style="width:60px;">Rev.</th>
                    <th style="width:100px;">Deadline</th>
                    <th style="width:100px;">Statut</th>
                    <th style="width:120px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td style="font-weight:500;max-width:180px;" title="{{ $document->name }}">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $document->name }}</div>
                        </td>
                        <td>
                            @if($document->code)
                                <span style="font-family:monospace;font-size:.73rem;color:var(--accent);">{{ $document->code }}</span>
                            @else
                                <span class="badge bg-secondary">Non codifie</span>
                            @endif
                        </td>
                        <td style="font-size:.72rem;max-width:140px;" title="{{ $document->type_libelle }}">{{ \Illuminate\Support\Str::limit($document->type_libelle, 28) }}</td>
                        <td><span class="badge badge-info">{{ \App\Models\Document::AIOS[$document->aio] ?? $document->aio }}</span></td>
                        <td>{{ $document->ligne }}</td>
                        <td style="font-size:.72rem;">{{ $document->phase_libelle }}</td>
                        <td style="font-family:monospace;font-size:.75rem;">v{{ $document->revision }}</td>
                        <td style="font-size:.72rem;">
                            @if($document->deadline)
                                <span style="{{ $document->deadline->isPast() ? 'color:var(--danger);' : '' }}">{{ $document->deadline->format('d/m/Y') }}</span>
                            @else
                                <span style="color:var(--muted);">-</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusConfig = [
                                    'draft' => ['badge-muted', 'Brouillon'],
                                    'pending_codification' => ['badge-warning', 'Codification'],
                                    'in_validation' => ['badge-info', 'En validation'],
                                    'rejected' => ['badge-danger', 'Rejete'],
                                    'finalized' => ['badge-success', 'Finalise'],
                                ];
                                $status = $statusConfig[$document->status] ?? ['badge-muted', $document->status];
                            @endphp
                            <span class="badge {{ $status[0] }}">{{ $status[1] }}</span>
                        </td>
<td>
                            <div class="dropdown" style="position:relative;">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleDropdown(event, {{ $document->id }})">
                                    Actions ▾
                                </button>
                                <ul id="dropdown-{{ $document->id }}" class="dropdown-menu dropdown-menu-end" style="display:none;position:absolute;right:0;z-index:1000;background:#1a2035;border:1px solid rgba(255,255,255,0.1);min-width:180px;">
                                    
                                    <li><a class="dropdown-item" href="{{ route('documents.download', $document) }}" style="color:#e5e7eb;"><i class="fas fa-link me-2"></i> Source</a></li>

                                    {{-- MODIFIER : draft/brouillon/rejected --}}
                                    @if(in_array($document->status, ['draft', 'brouillon', 'rejected']))
                                        <li><a class="dropdown-item" href="{{ route('documents.edit', $document) }}" style="color:#e5e7eb;"><i class="fas fa-edit me-2"></i> Modifier</a></li>
                                    @endif

                                    {{-- ENVOYER A L'ADMIN : draft sans code --}}
                                    @if(in_array($document->status, ['draft', 'brouillon']) && empty($document->code))
                                        <li>
                                            <form method="POST" action="{{ route('workflow.creator.send', $document) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item" style="color:#3b82f6;"><i class="fas fa-paper-plane me-2"></i> Envoyer a l admin</button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('documents.requestDeletion', $document) }}" onsubmit="return confirm('Supprimer ce document ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item" style="color:#ef4444;"><i class="fas fa-trash me-2"></i> Supprimer</button>
                                            </form>
                                        </li>
                                    @endif

                                    {{-- ENVOYER AU VALIDATEUR : draft AVEC code (document codifie) --}}
                                    @if(in_array($document->status, ['draft', 'brouillon']) && !empty($document->code))
                                        <li>
                                            <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item" style="color:#3b82f6;"><i class="fas fa-paper-plane me-2"></i> Envoyer au validateur</button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('documents.requestDeletion', $document) }}" onsubmit="return confirm('Supprimer ce document ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item" style="color:#ef4444;"><i class="fas fa-trash me-2"></i> Supprimer</button>
                                            </form>
                                        </li>
                                    @endif

                                    {{-- CONVERTIR EN PDF / SIGNER --}}
                                    @if(in_array($document->status, ['ready_for_pdf', 'pdf_converti']))
                                        @if($document->status === 'ready_for_pdf')
                                            <li><a class="dropdown-item" href="{{ route('workflow.creator.convert_pdf', ['id' => $document->id]) }}" target="_blank" style="color:#fbbf24;"><i class="fas fa-file-pdf me-2"></i> Convertir en PDF</a></li>
                                        @endif
                                        <li><a class="dropdown-item" href="#" onclick="showSignerModal({{ $document->id }})" style="color:#22c55e;"><i class="fas fa-signature me-2"></i> Signer et envoyer</a></li>
                                    @endif

                                    {{-- PDF FINAL --}}
                                    @if($document->status === 'archived')
                                        <li><a class="dropdown-item" href="{{ route('documents.export.pdf', $document) }}" style="color:#22c55e;"><i class="fas fa-file-pdf me-2"></i> PDF final</a></li>
                                    @endif

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

@foreach($documents as $document)
@if(in_array($document->status, ['ready_for_pdf', 'pdf_converti']))
<div class="modal" id="modalSigner{{ $document->id }}" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
  <div class="modal-dialog" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
      <div class="modal-header">
        <h5 class="modal-title">✍️ Signer et envoyer le document</h5>
        <button type="button" class="btn-close btn-close-white" onclick="closeSignerModal({{ $document->id }})"></button>
      </div>
      <div class="modal-body">
        <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.creator.sign', $document->id) }}">
          @csrf
          <div class="mb-3 p-3 rounded" style="background:rgba(255,255,255,0.05)">
            <small class="text-muted">Document :</small>
            <p class="mb-0 fw-bold">{{ $document->name }}</p>
            <small class="text-muted">Code : {{ $document->code }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">📎 Televerser le document signe *</label>
            <input type="file" name="document_signe" accept=".pdf,.docx" required class="form-control" style="background:#0f172a; color:white;">
            <small class="text-muted">Formats acceptes : PDF, DOCX</small>
          </div>
          <div class="mb-3">
            <label class="form-label">💬 Commentaire (optionnel)</label>
            <textarea name="commentaire" rows="3" class="form-control" style="background:#0f172a; color:white;" placeholder="Ajouter un commentaire..."></textarea>
          </div>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" onclick="closeSignerModal({{ $document->id }})">Annuler</button>
            <button type="submit" class="btn btn-primary">Signer et envoyer</button>
          </div>
        </form>
      </div>
  </div>
</div>
@endif
@endforeach

<script>
function toggleDropdown(event, id) {
    event.stopPropagation();
    var el = document.getElementById('dropdown-' + id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function showSignerModal(id) {
    document.getElementById('modalSigner' + id).style.display = 'block';
}
function closeSignerModal(id) {
    document.getElementById('modalSigner' + id).style.display = 'none';
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        var dropdowns = document.querySelectorAll('[id^="dropdown-"]');
        dropdowns.forEach(function(el) {
            el.style.display = 'none';
        });
    }
});
</script>

@endsection
