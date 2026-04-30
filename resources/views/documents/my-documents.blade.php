@extends('layouts.app')

@section('title', 'Mes documents')

@section('content')
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
        <div class="stat-label">Documents finalisés</div>
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
                                      'archived' => ['badge-success', 'Finalisé'],
                                  ];
                                   $status = $statusConfig[strtolower($document->status)] ?? ['badge-muted', $document->status];
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
      <div class="modal-dialog" style="background:#0f172a;border:1px solid #22c55e;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:10% auto;box-shadow:0 25px 60px rgba(34,197,94,0.15);">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
            <div style="width:42px;height:42px;background:rgba(34,197,94,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#22c55e;font-weight:700;">PDF</div>
            <div>
              <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Convertir en PDF</h5>
              <p style="color:#64748b;margin:0;font-size:0.8rem;">Téléversez la version PDF du document</p>
            </div>
            <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('modalConvertPdf{{ $document->id }}').style.display='none'" style="margin-left:auto;"></button>
          </div>
          <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.creator.convert_pdf', $document->id) }}">
            @csrf
            <div style="background:#1e293b;border-radius:10px;padding:12px 16px;margin-bottom:1.2rem;">
              <p style="color:#94a3b8;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;margin:0 0 4px;">DOCUMENT</p>
              <p style="color:white;font-weight:600;margin:0;">{{ $document->name }}</p>
              <p style="color:#3b82f6;font-size:0.82rem;margin:4px 0 0;">Code : {{ $document->code }}</p>
            </div>
            <div style="margin-bottom:1.5rem;">
              <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                TÉLÉVERSER LE PDF CONVERTI <span style="color:#ef4444;">*</span>
              </label>
              <label for="pdfFile{{ $document->id }}" style="display:flex;align-items:center;gap:12px;background:#1e293b;border:2px dashed #334155;border-radius:10px;padding:16px;cursor:pointer;transition:all 0.2s;"
                     onmouseover="this.style.borderColor='#22c55e'" onmouseout="this.style.borderColor='#334155'">
                <div style="width:36px;height:36px;background:rgba(34,197,94,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#22c55e;flex-shrink:0;">+</div>
                <div>
                  <p style="color:white;font-weight:500;margin:0;font-size:0.9rem;" id="pdfFileName{{ $document->id }}">Choisir un fichier PDF</p>
                  <p style="color:#64748b;font-size:0.78rem;margin:2px 0 0;">Formats acceptés : PDF uniquement</p>
                </div>
              </label>
              <input type="file" id="pdfFile{{ $document->id }}" name="pdf_file" accept=".pdf" required style="display:none;"
                     onchange="document.getElementById('pdfFileName{{ $document->id }}').textContent = this.files[0]?.name || 'Choisir un fichier PDF'">
            </div>
            <div style="display:flex;gap:12px;justify-content:flex-end;">
              <button type="button" onclick="document.getElementById('modalConvertPdf{{ $document->id }}').style.display='none'"
                style="padding:10px 24px;background:#1e293b;color:#94a3b8;border:1px solid #334155;border-radius:10px;cursor:pointer;font-size:0.9rem;transition:all 0.2s;"
                onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#1e293b'">
                Annuler
              </button>
              <button type="submit"
                style="padding:10px 24px;background:#22c55e;color:white;border:none;border-radius:10px;cursor:pointer;font-size:0.9rem;font-weight:600;transition:all 0.2s;"
                onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                Convertir
              </button>
            </div>
          </form>
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




