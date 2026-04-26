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
                @if($status === 'draft')
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
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleMenu(this)">Actions ▾</button>
                            <ul class="action-menu" style="display:none;position:fixed;background:#1a2035;border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:8px 0;z-index:9999;min-width:160px;list-style:none;margin:0;">
                                @if(in_array($document->status, ['draft', 'rejected']))
                                    <li><a class="dropdown-item" href="{{ route('documents.edit', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-edit"></i> Modifier</a></li>
                                @endif

                                @if($document->status === 'draft' && empty($document->code))
                                    <li>
                                        <form method="POST" action="{{ route('workflow.creator.send', $document) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#e5e7eb;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-paper-plane"></i> Envoyer a l admin</button>
                                        </form>
                                    </li>
                                @endif

                                @if($document->status === 'draft' && ! empty($document->code))
                                    <li>
                                        <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#e5e7eb;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-paper-plane"></i> Envoyer pour validation</button>
                                        </form>
                                    </li>
                                @endif

                                @if($document->status === 'rejected' && ! empty($document->code))
                                    <li><a class="dropdown-item" href="{{ route('documents.edit', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-edit"></i> Modifier</a></li>
                                    <li>
                                        <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#e5e7eb;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-paper-plane"></i> Renvoyer pour validation</button>
                                        </form>
                                    </li>
                                @endif

                                @if($document->status === 'ready_for_pdf')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('documents.convert.pdf', $document) }}" target="_blank" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#fbbf24;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-file-pdf"></i> Convertir en PDF</a>
                                    </li>
                                    <li><hr class="dropdown-divider" style="margin:4px 0;"></li>
                                    <li>
                                        <a class="dropdown-item text-success" href="#" data-bs-toggle="modal" data-bs-target="#modalSigner{{ $document->id }}" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#22c55e;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-signature"></i> Signer et envoyer</a>
                                    </li>
                                @endif

                                @if($document->status === 'draft')
                                    <li style="border-top:1px solid rgba(255,255,255,0.1);margin:4px 0;"></li>
                                    <li>
                                        <form method="POST" action="{{ route('documents.requestDeletion', $document) }}" onsubmit="return confirm('Supprimer definitivement ce document ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#f87171;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-trash"></i> Supprimer</button>
                                        </form>
                                    </li>
                                @endif

                                <li><a class="dropdown-item" href="{{ route('documents.download', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-code"></i> Source</a></li>
                                @if($document->status === 'finalized')
                                    <li><a class="dropdown-item" href="{{ route('documents.export.pdf', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-file-pdf"></i> PDF final</a></li>
                                @endif
                            </ul>
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
<div class="modal fade" id="modalSigner{{ $document->id }}" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="background:#1a2035; color:white; border:1px solid rgba(255,255,255,0.1)">
      <div class="modal-header">
        <h5 class="modal-title">✍️ Signer et envoyer le document</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.creator.sign', $document->id) }}">
          @csrf
          <div class="mb-3 p-3 rounded" style="background:rgba(255,255,255,0.05)">
            <small class="text-muted">Document :</small>
            <p class="mb-0 fw-bold">{{ $document->nom }}</p>
            <small class="text-muted">Code : {{ $document->code }}</small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">📎 Téléverser le document signé *</label>
            <input type="file" name="document_signe" accept=".pdf,.docx" required class="form-control" style="background:#0f172a; color:white;">
            <small class="text-muted">Formats acceptés : PDF, DOCX</small>
          </div>
          <div class="mb-3">
            <label class="form-label">💬 Commentaire (optionnel)</label>
            <textarea name="commentaire" rows="3" class="form-control" style="background:#0f172a; color:white;" placeholder="Ajouter un commentaire..."></textarea>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="confirm{{ $document->id }}" required>
            <label class="form-check-label" for="confirm{{ $document->id }}">Je confirme avoir signé ce document et l'envoyer au validateur</label>
          </div>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success">✍️ Signer et envoyer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endforeach

@endsection
