@extends('layouts.app')

@section('title', 'Documents a approuver')

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
    <a href="{{ route('workflow.approver.index', ['filter' => 'processed']) }}" class="stat-card {{ $filter === 'processed' ? 'active' : '' }}">
        <div class="stat-label">Approuvés</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['processed'] }}</div>
        <div class="stat-meta">Historique personnel</div>
    </a>
    <a href="{{ route('workflow.approver.index', ['filter' => 'rejected']) }}" class="stat-card {{ $filter === 'rejected' ? 'active' : '' }}">
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
                <div id="sign-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 0;">
                    <div style="background:#0f172a;border:1px solid #3b82f6;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:20px auto;box-shadow:0 25px 60px rgba(59,130,246,0.15);max-height:calc(100vh - 40px);overflow-y:auto;position:relative;">
                        <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.approver.sign', $doc) }}">
                            @csrf
                            <!-- Header -->
                            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
                                <div style="width:42px;height:42px;background:rgba(59,130,246,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:bold;color:#3b82f6;">S</div>
                                <div>
                                    <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Signer et envoyer le document</h5>
                                    <p style="color:#64748b;margin:0;font-size:0.8rem;">Téléversez le document signé pour continuer le workflow</p>
                                </div>
                                <button type="button" onclick="closeSignModal('{{ $doc->id }}')" style="margin-left:auto;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:6px;">X</button>
                            </div>

                            <!-- Document info -->
                            <div style="background:#1e293b;border-radius:10px;padding:12px 16px;margin-bottom:1.2rem;">
                                <p style="color:#94a3b8;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;margin:0 0 4px;">DOCUMENT</p>
                                <p style="color:white;font-weight:600;margin:0;">{{ $doc->name }}</p>
                                <p style="color:#3b82f6;font-size:0.82rem;margin:4px 0 0;">Code : {{ $doc->code }}</p>
                            </div>

                            <!-- File upload -->
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

                            <!-- Comment -->
                            <div style="margin-bottom:1.5rem;">
                                <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                                    COMMENTAIRE <span style="color:#64748b;font-size:0.75rem;">(optionnel)</span>
                                </label>
                                <textarea name="commentaire" rows="3"
                                    placeholder="Ajouter un commentaire..."
                                    style="width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;padding:12px;font-size:0.9rem;resize:vertical;outline:none;box-sizing:border-box;transition:border-color 0.2s;"
                                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#334155'"></textarea>
                            </div>

                            <!-- Buttons -->
                            <div style="display:flex;gap:12px;justify-content:flex-end;">
                                <button type="button" onclick="closeSignModal('{{ $doc->id }}')"
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
                         <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Télécharger</a>
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
                        <td style="font-family:monospace;font-size:.75rem;">{{ $doc->revision }}</td>
                         <td>{{ $doc->creator->name ?? '-' }} {{ $doc->creator->prenom ?? '' }}</td>
                        <td style="font-size:.72rem;">
                            @if($doc->deadline)
                                <span style="{{ $doc->deadline->isPast() ? 'color:var(--danger)' : '' }}">
                                    {{ $doc->deadline->format('d/m/Y') }}
                                </span>
                            @else
                                 -
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('documents.download', $doc) }}"><i class="fas fa-download me-2"></i>Télécharger</a></li>
                                    @if(($doc->status === 'in_approbation' || $doc->status === 'approbation') && $doc->current_role === 'approver')
                                    <li>
                                        <button type="button" class="dropdown-item" style="color:#4ade80;cursor:pointer;"
                                                onclick="openApproveModal('{{ $doc->id }}')">
                                            <i class="fas fa-check me-2"></i>Approuver
                                        </button>
                                    </li>
                                    <li>
                                         <button type="button" class="dropdown-item" style="color:#f87171;cursor:pointer;"
                                                 onclick="openRejectModal('{{ route('workflow.approver.reject', $doc) }}')">
                                             <i class="fas fa-times me-2"></i>Rejeter
                                         </button>
                                     </li>
                                    @endif
                                    @if(in_array($doc->status, ['signing_approver']) && $doc->current_role === 'approver')
                                    <li>
                                        <button type="button" class="dropdown-item" style="color:#c084fc;cursor:pointer;"
                                                onclick="openTableSignModal('{{ $doc->id }}')">
                                            <i class="fas fa-signature me-2"></i>Signer PDF
                                        </button>
                                    </li>
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


<!-- Modal de signature (alertes) -->
@if($showAlertes)
@foreach($alertes as $doc)
                <div id="sign-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 0;">
                    <div style="background:#0f172a;border:1px solid #3b82f6;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:20px auto;box-shadow:0 25px 60px rgba(59,130,246,0.15);max-height:calc(100vh - 40px);overflow-y:auto;position:relative;">
        <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.approver.sign', $doc) }}">
            @csrf
            <!-- Header -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
                <div style="width:42px;height:42px;background:rgba(59,130,246,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:bold;color:#3b82f6;">S</div>
                <div>
                    <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Signer et envoyer le document</h5>
                    <p style="color:#64748b;margin:0;font-size:0.8rem;">Téléversez le document signé pour continuer le workflow</p>
                </div>
                <button type="button" onclick="closeSignModal('{{ $doc->id }}')" style="margin-left:auto;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:6px;">X</button>
            </div>

            <!-- Document info -->
            <div style="background:#1e293b;border-radius:10px;padding:12px 16px;margin-bottom:1.2rem;">
                <p style="color:#94a3b8;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;margin:0 0 4px;">DOCUMENT</p>
                <p style="color:white;font-weight:600;margin:0;">{{ $doc->name }}</p>
                <p style="color:#3b82f6;font-size:0.82rem;margin:4px 0 0;">Code : {{ $doc->code }}</p>
            </div>

            <!-- File upload -->
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

            <!-- Comment -->
            <div style="margin-bottom:1.5rem;">
                <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                    COMMENTAIRE <span style="color:#64748b;font-size:0.75rem;">(optionnel)</span>
                </label>
                <textarea name="commentaire" rows="3"
                    placeholder="Ajouter un commentaire..."
                    style="width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;padding:12px;font-size:0.9rem;resize:vertical;outline:none;box-sizing:border-box;transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#334155'"></textarea>
            </div>

            <!-- Buttons -->
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" onclick="closeSignModal('{{ $doc->id }}')"
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
@endforeach
@endif

<!-- Modal de signature (table) -->
@foreach($documents as $doc)
@if(($doc->status === 'in_approbation' || $doc->status === 'approbation') && $doc->current_role === 'approver')
<div id="approve-modal-{{ $doc->id }}" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);">
    <div style="background:#1f2937;margin:15% auto;padding:1.5rem;border-radius:8px;max-width:400px;border:1px solid rgba(255,255,255,0.1);">
        <h4 style="margin-bottom:1rem;color:white;">Approuver le document</h4>
         <p style="margin-bottom:1.5rem;color:#9ca3af;">Approuver <strong style="color:white;">{{ $doc->name }}</strong> et l'envoyer à l'admin ?</p>
        <form action="{{ route('workflow.approver.approve', $doc) }}" method="POST">
            @csrf
            <div style="display:flex;gap:.5rem;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('approve-modal-{{ $doc->id }}').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-sm" style="background:rgba(34,197,94,0.2);border:1px solid rgba(34,197,94,0.5);color:#4ade80;">Confirmer</button>
            </div>
        </form>
    </div>
</div>

                @endif
                @endforeach

@foreach($documents as $doc)
<div id="table-sign-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 0;">
    <div style="background:#0f172a;border:1px solid #3b82f6;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:20px auto;box-shadow:0 25px 60px rgba(59,130,246,0.15);max-height:calc(100vh - 40px);overflow-y:auto;position:relative;">
        <form method="POST" enctype="multipart/form-data" action="{{ route('workflow.approver.sign', $doc) }}">
            @csrf
            <!-- Header -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
                <div style="width:42px;height:42px;background:rgba(59,130,246,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:bold;color:#3b82f6;">S</div>
                <div>
                    <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Signer et envoyer le document</h5>
                    <p style="color:#64748b;margin:0;font-size:0.8rem;">Téléversez le document signé pour continuer le workflow</p>
                </div>
                <button type="button" onclick="closeTableSignModal('{{ $doc->id }}')" style="margin-left:auto;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:6px;">X</button>
            </div>

            <!-- Document info -->
            <div style="background:#1e293b;border-radius:10px;padding:12px 16px;margin-bottom:1.2rem;">
                <p style="color:#94a3b8;font-size:0.75rem;font-weight:600;letter-spacing:0.05em;margin:0 0 4px;">DOCUMENT</p>
                <p style="color:white;font-weight:600;margin:0;">{{ $doc->name }}</p>
                <p style="color:#3b82f6;font-size:0.82rem;margin:4px 0 0;">Code : {{ $doc->code }}</p>
            </div>

            <!-- File upload -->
            <div style="margin-bottom:1.2rem;">
                <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                    TÉLÉVERSER LE DOCUMENT SIGNÉ <span style="color:#ef4444;">*</span>
                </label>
                <label for="tableSignedFile{{ $doc->id }}" style="display:flex;align-items:center;gap:12px;background:#1e293b;border:2px dashed #334155;border-radius:10px;padding:16px;cursor:pointer;transition:all 0.2s;"
                       onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#334155'">
                    <div style="width:36px;height:36px;background:rgba(59,130,246,0.15);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;color:#3b82f6;">+</div>
                    <div>
                        <p style="color:white;font-weight:500;margin:0;font-size:0.9rem;" id="tableSignedFileName{{ $doc->id }}">Choisir un fichier PDF</p>
                        <p style="color:#64748b;font-size:0.78rem;margin:2px 0 0;">Format accepté : PDF uniquement</p>
                    </div>
                </label>
                <input type="file" id="tableSignedFile{{ $doc->id }}" name="document_signe" accept=".pdf" required style="display:none;"
                       onchange="document.getElementById('tableSignedFileName{{ $doc->id }}').textContent = this.files[0]?.name || 'Choisir un fichier PDF'">
            </div>

            <!-- Comment -->
            <div style="margin-bottom:1.5rem;">
                <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:8px;">
                    COMMENTAIRE <span style="color:#64748b;font-size:0.75rem;">(optionnel)</span>
                </label>
                <textarea name="commentaire" rows="3"
                    placeholder="Ajouter un commentaire..."
                    style="width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;padding:12px;font-size:0.9rem;resize:vertical;outline:none;box-sizing:border-box;transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#334155'"></textarea>
            </div>

            <!-- Buttons -->
            <div style="display:flex;gap:12px;justify-content:flex-end;">
                <button type="button" onclick="closeTableSignModal('{{ $doc->id }}')"
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
                <div style="font-size:.74rem;color:var(--muted);">{{ $doc->code ?: 'Sans code' }} | {{ $doc->revision }}</div>
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
function openSignModal(id) {
    document.getElementById('sign-modal-' + id).style.display = 'block';
}
function closeSignModal(id) {
    document.getElementById('sign-modal-' + id).style.display = 'none';
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
function openTableSignModal(id) {
    document.getElementById('table-sign-modal-' + id).style.display = 'block';
}
function openApproveModal(id) {
    document.getElementById('approve-modal-' + id).style.display = 'block';
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




