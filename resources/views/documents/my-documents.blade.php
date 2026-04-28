@extends('layouts.app')

@section('title', 'Mes documents')

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
<div class="cards-row">
    <a href="{{ route('documents.my') }}" class="stat-card {{ !$status ? 'active' : '' }}">
        <div class="stat-label">Brouillons</div>
        <div class="stat-value">{{ (int) ($stats->drafts ?? 0) }}</div>
        <div class="stat-meta">Documents encore modifiables</div>
    </a>
    <a href="{{ route('documents.my', ['status' => 'pending_codification']) }}" class="stat-card {{ $status === 'pending_codification' ? 'active' : '' }}">
        <div class="stat-label">Codification</div>
        <div class="stat-value" style="color:#f59e0b;">{{ (int) ($stats->pending_codification ?? 0) }}</div>
        <div class="stat-meta">En attente chez l admin</div>
    </a>
    <a href="{{ route('documents.my', ['status' => 'in_validation']) }}" class="stat-card {{ $status === 'in_validation' ? 'active' : '' }}">
        <div class="stat-label">Validation</div>
        <div class="stat-value" style="color:#38bdf8;">{{ (int) ($stats->in_validation ?? 0) }}</div>
        <div class="stat-meta">Documents en circuit</div>
    </a>
    <a href="{{ route('documents.my', ['status' => 'ready_for_pdf']) }}" class="stat-card {{ $status === 'ready_for_pdf' ? 'active' : '' }}">
        <div class="stat-label">Pret pour PDF</div>
        <div class="stat-value" style="color:#10b981;">{{ (int) ($stats->ready_for_pdf ?? 0) }}</div>
        <div class="stat-meta">Documents a convertir en PDF</div>
    </a>
    <a href="{{ route('documents.my', ['status' => 'rejected']) }}" class="stat-card {{ $status === 'rejected' ? 'active' : '' }}">
        <div class="stat-label">Rejetes</div>
        <div class="stat-value" style="color:#f87171;">{{ (int) ($stats->rejected ?? 0) }}</div>
        <div class="stat-meta">A corriger et renvoyer</div>
    </a>
    <a href="{{ route('documents.my', ['status' => 'archived']) }}" class="stat-card {{ $status === 'archived' ? 'active' : '' }}">
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
        @if(auth()->user()->role === 'creator')
            <a href="{{ route('documents.create') }}" class="btn btn-primary">Nouveau document</a>
        @endif
    </div>

    @if($documents->isEmpty())
        <div style="text-align:center;padding:2rem;color:var(--muted);">
            <div>Aucun document pour le moment.</div>
            @if(auth()->user()->role === 'creator')
                <a href="{{ route('documents.create') }}" class="btn" style="margin-top:.75rem;">Créer mon premier document</a>
            @endif
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
                        <td style="font-family:monospace;font-size:.75rem;">{{ $document->revision }}</td>
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
                                      'EN_MODIFICATION' => ['badge-danger', 'REFUS -  modifier'],
                                      'pending_codification' => ['badge-warning', 'Codification'],
                                      'in_validation' => ['badge-info', 'En validation'],
                                      'ready_for_pdf' => ['badge-success', 'Pret pour PDF'],
                                      'pdf_converted' => ['badge-success', 'PDF converti'],
                                      'rejected' => ['badge-danger', 'Rejete'],
                                      'archived' => ['badge-success', 'Finalise'],
                                  ];
                                  $status = $statusConfig[strtolower($document->status)]  ['badge-muted', $document->status];
                             @endphp
                             <span class="badge {{ $status[0] }}">{{ $status[1] }}</span>
                             @if($document->status === 'EN_MODIFICATION' && $document->commentaire_rejet)
                                 <div style="font-size:0.7rem; color:#ef4444; margin-top:4px;" title="{{ $document->commentaire_rejet }}">
                                     <i class="fas fa-exclamation-circle me-1"></i>{{ \Illuminate\Support\Str::limit($document->commentaire_rejet, 50) }}
                                 </div>
                             @endif
                        </td>
<td>
                             <div class="dropdown">
                                 <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                     Actions
                                 </button>
                                 <ul class="dropdown-menu dropdown-menu-end">
                                     <li><a class="dropdown-item" href="{{ route('documents.download', $document) }}"><i class="fas fa-download me-2"></i>Télécharger</a></li>
                                     @if(in_array($document->status, ['draft', 'rejected', 'EN_MODIFICATION']) && auth()->user()->role === 'creator')
                                         <li><a class="dropdown-item" href="{{ route('documents.edit', $document) }}"><i class="fas fa-edit me-2"></i>Modifier</a></li>
                                     @endif
                                     @if($document->status === 'draft' && empty($document->code) && auth()->user()->role === 'creator')
                                         <li>
                                             <form method="POST" action="{{ route('workflow.creator.send', $document) }}">
                                                 @csrf
                                                 <button type="submit" class="dropdown-item"><i class="fas fa-paper-plane me-2"></i>Envoyer a l admin</button>
                                             </form>
                                         </li>
                                     @endif
                                     @if(in_array($document->status, ['draft', 'EN_MODIFICATION']) && !empty($document->code) && auth()->user()->role === 'creator')
                                         <li>
                                             <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                                 @csrf
                                                 <button type="submit" class="dropdown-item"><i class="fas fa-paper-plane me-2"></i>Envoyer au validateur</button>
                                             </form>
                                         </li>
                                     @endif
                                     @if($document->status === 'ready_for_pdf' && auth()->user()->role === 'creator')
                                         <li><a class="dropdown-item" href="#" onclick="document.getElementById('modalConvertPdf{{ $document->id }}').style.display='block'"><i class="fas fa-file-pdf me-2"></i>Convertir en PDF</a></li>
                                     @endif
                                     @if(strtolower($document->status) === 'pdf_converted' && auth()->user()->role === 'creator')
                                         <li><a class="dropdown-item" href="{{ route('documents.sign.form', $document->id) }}"><i class="fas fa-signature me-2"></i>Signer</a></li>
                                     @endif
                                     @if($document->status === 'archived')
                                         <li><a class="dropdown-item" href="{{ route('documents.export.pdf', $document) }}"><i class="fas fa-file-pdf me-2"></i>PDF final</a></li>
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

    @foreach($documents as $document)
    @if($document->status === 'ready_for_pdf' && auth()->user()->role === 'creator')
    <div class="modal" id="modalConvertPdf{{ $document->id }}" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
      <div class="modal-dialog" style="background:#1a2035;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:500px;">
          <div class="modal-header">
            <h5 class="modal-title"> Convertir en PDF</h5>
            <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('modalConvertPdf{{ $document->id }}').style.display='none'"></button>
          </div>
          <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.creator.convert_pdf', $document->id) }}">
              @csrf
              <div class="mb-3 p-3 rounded" style="background:rgba(255,255,255,0.05)">
                <small class="text-muted">Document :</small>
                <p class="mb-0 fw-bold">{{ $document->name }}</p>
                <small class="text-muted">Code : {{ $document->code }}</small>
              </div>
              <div class="mb-3">
                <label class="form-label fw-bold"> Televerser le PDF converti *</label>
                <input type="file" name="pdf_file" accept=".pdf" required class="form-control" style="background:#0f172a; color:white;">
                <small class="text-muted">Formats acceptes : PDF</small>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalConvertPdf{{ $document->id }}').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary">Convertir</button>
              </div>
            </form>
          </div>
      </div>
    </div>
    @endif
    @endforeach
</div>
<script>
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




