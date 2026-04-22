@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="cards-row" style="margin-bottom:1.25rem;">
    <a href="{{ route('admin.documents.index', ['status' => 'created', 'range' => $range]) }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">Documents crees</div>
        <div class="stat-value">{{ $createdCount }}</div>
        <div class="stat-meta">Periode : {{ $range === 'week' ? '7 jours' : ($range === 'month' ? '30 jours' : '1 an') }}</div>
    </a>
    <a href="{{ route('admin.documents.codification') }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">En codification</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $pendingCodification }}</div>
        <div class="stat-meta">En attente chez l admin</div>
    </a>
    <a href="{{ route('admin.documents.index', ['status' => 'in_validation', 'range' => $range]) }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">En validation</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $inValidationCount }}</div>
        <div class="stat-meta">Workflow en cours</div>
    </a>
    <a href="{{ route('admin.documents.index', ['status' => 'finalized', 'range' => $range]) }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">Finalises</div>
        <div class="stat-value" style="color:#4ade80;">{{ $finalizedCount }}</div>
        <div class="stat-meta">Signes et archives</div>
    </a>
    <a href="{{ route('admin.documents.index', ['status' => 'rejected', 'range' => $range]) }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">Rejetes</div>
        <div class="stat-value" style="color:#f87171;">{{ $rejectedCount }}</div>
        <div class="stat-meta">Retournes au createur</div>
    </a>
    <a href="{{ route('admin.users.index') }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">Utilisateurs</div>
        <div class="stat-value">{{ $totalUsers }}</div>
        <div class="stat-meta">Tous roles confondus</div>
    </a>
    <a href="{{ route('admin.users.pending') }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">Comptes en attente</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $pendingUsers }}</div>
        <div class="stat-meta">Validation admin requise</div>
    </a>
    <a href="{{ route('admin.users.index', ['filter' => 'unverified']) }}" class="stat-card" style="cursor:pointer;text-decoration:none;color:inherit;">
        <div class="stat-label">Codes en attente</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $awaitingVerification }}</div>
        <div class="stat-meta">Utilisateurs approuves non verifies</div>
    </a>
</div>

<div style="margin-bottom:1.25rem;">
    <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">Creer un document</a>
</div>

<div class="cards-row">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Pilotage utilisateurs</div>
                <div class="card-sub">Acces direct aux ecrans de gestion admin.</div>
            </div>
        </div>
        <div style="display:grid;gap:.6rem;">
            <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Ouvrir la gestion des utilisateurs</a>
            <a href="{{ route('admin.users.pending') }}" class="btn btn-ghost">Traiter les comptes en attente</a>
            <a href="{{ route('admin.documents.codification') }}" class="btn btn-ghost">Ouvrir la codification</a>
            <a href="{{ route('admin.documents.export.follow-up-pdf') }}" class="btn btn-ghost">Exporter PDF</a>
            <a href="{{ route('admin.documents.export.follow-up-word') }}" class="btn btn-ghost">Exporter Word</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Repartition des roles</div>
                <div class="card-sub">Vue rapide des profils actifs.</div>
            </div>
        </div>
        <div style="display:grid;gap:.55rem;">
            <div style="display:flex;justify-content:space-between;gap:1rem;">
                <span>Createurs</span>
                <strong>{{ $usersByRole['creator'] ?? 0 }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem;">
                <span>Validateurs</span>
                <strong>{{ $usersByRole['validator'] ?? 0 }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem;">
                <span>Approbateurs</span>
                <strong>{{ $usersByRole['approver'] ?? 0 }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;gap:1rem;">
                <span>Admins</span>
                <strong>{{ $usersByRole['admin'] ?? 0 }}</strong>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Supervision globale</div>
            <div class="card-sub">Tous les documents du systeme selon la periode selectionnee.</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <form method="GET" style="display:flex;align-items:center;gap:.4rem;margin:0;">
                <select name="range" onchange="this.form.submit()">
                    <option value="week"  @selected($range==='week')>7 jours</option>
                    <option value="month" @selected($range==='month')>30 jours</option>
                    <option value="year"  @selected($range==='year')>1 an</option>
                </select>
            </form>
            @if($pendingCodification > 0)
                <a href="{{ route('admin.documents.codification') }}" class="btn btn-sm">Codification ({{ $pendingCodification }})</a>
            @endif
            @if($pendingUsers > 0)
                <a href="{{ route('admin.users.pending') }}" class="btn btn-sm">Comptes ({{ $pendingUsers }})</a>
            @endif
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
                            <a href="{{ route('admin.documents.edit', $doc) }}" class="btn btn-ghost btn-sm">Modifier</a>
                            @if(!$doc->is_fully_signed)
                                <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" style="display:inline;" onsubmit="return confirm('Supprimer ce document ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm btn-danger">Supprimer</button>
                                </form>
                            @endif
                            @if($doc->status === 'in_validation' && $doc->current_role === 'admin')
                                @php
                                    $hasAdminSignature = $doc->signatures->where('role', 'admin')->isNotEmpty();
                                @endphp
                                @if($hasAdminSignature)
                                    <button type="button" class="btn btn-sm" onclick="openSignModal('{{ $doc->id }}')">Signer et finaliser</button>
                                @else
                                    <form method="POST" action="{{ route('admin.workflow.validate', $doc) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Valider et envoyer au createur</button>
                                    </form>
                                @endif
                                <button type="button" class="btn btn-ghost btn-sm btn-danger" onclick="toggleReject('rej-{{ $doc->id }}')">Rejeter</button>
                            @endif
                            <div id="rej-{{ $doc->id }}" style="display:none;margin-top:.5rem;">
                                <form method="POST" action="{{ route('admin.workflow.reject', $doc) }}" style="display:flex;flex-direction:column;gap:.4rem;">
                                    @csrf
                                    <textarea name="message" placeholder="Motif du rejet" required style="width:100%;min-height:60px;padding:.3rem;border:1px solid var(--border);border-radius:.25rem;"></textarea>
                                    <input type="date" name="deadline" placeholder="Delai (optionnel)" style="padding:.3rem;border:1px solid var(--border);border-radius:.25rem;">
                                    <button type="submit" class="btn btn-sm btn-danger">Confirmer le rejet</button>
                                </form>
                            </div>
                            @if($doc->is_fully_signed)
                                <a href="{{ route('admin.documents.export.pdf', $doc) }}" class="btn btn-ghost btn-sm">PDF</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun document sur cette periode.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $documents->links() }}</div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Journaux recents</div>
            <div class="card-sub">Dernieres actions tracees dans le systeme.</div>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Cible</th>
            </tr>
            </thead>
            <tbody>
            @forelse($recentLogs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? 'Systeme' }}</td>
                    <td>{{ $log->action }}</td>
                    <td style="font-size:.72rem;">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center;color:var(--muted);padding:1.25rem;">Aucun journal disponible.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modals de signature admin -->
@foreach($documents as $doc)
@if($doc->status === 'in_validation' && $doc->current_role === 'admin')
@php
    $hasAdminSignature = $doc->signatures->where('role', 'admin')->isNotEmpty();
@endphp
<div id="sign-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#020617;border-radius:.5rem;padding:1.5rem;max-width:400px;width:90%;">
        <h3 style="margin:0 0 1rem 0;color:#e5e7eb;">
            @if($hasAdminSignature)
                Signer et finaliser le document (Admin)
            @else
                Valider le document (Admin)
            @endif
        </h3>
        <p style="margin:0 0 1rem 0;font-size:.9rem;color:#9ca3af;">
            @if($hasAdminSignature)
                Téléchargez le document PDF signé, vérifiez-le et appliquez votre signature finale.
            @else
                Téléchargez le document, appliquez votre validation et envoyez-le au créateur pour conversion PDF.
            @endif
        </p>
        <div style="display:flex;gap:.5rem;margin-bottom:1rem;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="downloadDocument('{{ $doc->id }}')">Télécharger</button>
        </div>
        <form method="POST" action="{{ route('admin.workflow.sign', $doc) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="signed_file" accept=".pdf,.docx,.xlsx" required style="width:100%;padding:.5rem;border:1px solid var(--border);border-radius:.25rem;background:#020617;color:#e5e7eb;">
            <div style="display:flex;gap:.5rem;margin-top:1rem;">
                <button type="submit" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);">
                    @if($hasAdminSignature)
                        Signer et finaliser
                    @else
                        Valider et envoyer
                    @endif
                </button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeSignModal('{{ $doc->id }}')">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

@endsection

<script>
function toggleReject(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function openSignModal(id) {
    document.getElementById('sign-modal-' + id).style.display = 'block';
}
function closeSignModal(id) {
    document.getElementById('sign-modal-' + id).style.display = 'none';
}
function downloadDocument(id) {
    window.open('/documents/' + id + '/download', '_blank');
}
</script>
