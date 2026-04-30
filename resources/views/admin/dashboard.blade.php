@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="cards-row" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 1.25rem;">
    <a href="{{ route('admin.documents.index', ['status' => 'rejected', 'range' => $range]) }}" class="stat-card" style="position: relative;">
        <i class="fas fa-times-circle" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #ef4444;"></i>
        <div class="stat-label">Rejetes</div>
        <div style="font-size: 2rem; font-weight: 700; color: #ef4444;">{{ $rejectedCount }}</div>
        <div class="stat-meta">Retournes au createur</div>
    </a>
    <a href="{{ route('admin.users.index') }}" class="stat-card" style="position: relative;">
        <i class="fas fa-users" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #0ea5e9;"></i>
        <div class="stat-label">Utilisateurs</div>
        <div style="font-size: 2rem; font-weight: 700; color: #0ea5e9;">{{ $totalUsers }}</div>
        <div class="stat-meta">Tous roles confondus</div>
    </a>
    <a href="{{ route('admin.users.pending') }}" class="stat-card" style="position: relative;">
        <i class="fas fa-clock" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #f59e0b;"></i>
        <div class="stat-label">Comptes en attente</div>
        <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;">{{ $pendingUsers }}</div>
        <div class="stat-meta">Validation admin requise</div>
    </a>
    <a href="{{ route('admin.users.index', ['filter' => 'unverified']) }}" class="stat-card" style="position: relative;">
        <i class="fas fa-key" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #38bdf8;"></i>
        <div class="stat-label">Codes en attente</div>
        <div style="font-size: 2rem; font-weight: 700; color: #38bdf8;">{{ $awaitingVerification }}</div>
        <div class="stat-meta">Utilisateurs approuves non verifies</div>
    </a>
</div>

<div class="cards-row" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 1.25rem;">
    <a href="{{ route('admin.documents.index', ['status' => 'created', 'range' => $range]) }}" class="stat-card">
        <div class="stat-label">Documents crees</div>
        <div style="font-size: 2rem; font-weight: 700;">{{ $createdCount }}</div>
        <div class="stat-meta">Periode : {{ $range === 'week' ? '7 jours' : ($range === 'month' ? '30 jours' : '1 an') }}</div>
    </a>
    <a href="{{ route('admin.documents.codification') }}" class="stat-card" style="position: relative;">
        <i class="fas fa-code" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #f59e0b;"></i>
        <div class="stat-label">En codification</div>
        <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;">{{ $pendingCodification }}</div>
        <div class="stat-meta">En attente chez l admin</div>
    </a>
    <a href="{{ route('admin.documents.index', ['status' => 'validation_admin', 'range' => $range]) }}" class="stat-card" style="position: relative;">
        <i class="fas fa-user-check" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #8b5cf6;"></i>
        <div class="stat-label">A approuver</div>
        <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6;">{{ $pendingAdminValidation }}</div>
        <div class="stat-meta">En attente de validation</div>
    </a>
    <a href="{{ route('admin.documents.index', ['status' => 'in_validation', 'range' => $range]) }}" class="stat-card" style="position: relative;">
        <i class="fas fa-check-circle" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #38bdf8;"></i>
        <div class="stat-label">En validation</div>
        <div style="font-size: 2rem; font-weight: 700; color: #38bdf8;">{{ $inValidationCount }}</div>
        <div class="stat-meta">Workflow en cours</div>
    </a>
    <a href="{{ route('admin.documents.index', ['status' => 'archived', 'range' => $range]) }}" class="stat-card" style="position: relative;">
        <i class="fas fa-file-signature" style="position: absolute; top: 0.75rem; right: 0.75rem; font-size: 1.5rem; color: #22c55e;"></i>
        <div class="stat-label">Finalises</div>
        <div style="font-size: 2rem; font-weight: 700; color: #22c55e;">{{ $finalizedCount }}</div>
        <div class="stat-meta">Signes et archives</div>
    </a>
</div>

<div class="card" style="margin-bottom: 1.25rem;">
    <div class="card-header">
        <div>
            <div class="card-title">Activite recente</div>
            <div class="card-sub">Documents crees, valides et rejetes sur 7 jours</div>
        </div>
    </div>
    <div style="height: 300px; padding: 1rem;">
        <canvas id="activityChart"></canvas>
    </div>
</div>

<div class="cards-row" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Pilotage utilisateurs</div>
                <div class="card-sub">Acces direct aux ecrans de gestion admin.</div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <a href="{{ route('admin.users.index') }}" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700; padding: 12px; border-radius: 6px; background: #0ea5e9; color: #fff; border: 1px solid #0ea5e9; text-decoration: none;">
                <i class="fas fa-users"></i> Ouvrir la gestion des utilisateurs
            </a>
            <a href="{{ route('admin.users.pending') }}" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 6px; background: transparent; color: #f59e0b; border: 1px solid #f59e0b; text-decoration: none;">
                <i class="fas fa-user-clock"></i> Traiter les comptes en attente
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-file-alt" style="color:#c084fc;margin-right:.5rem;"></i>Journaux & Traabilit</div>
                <div class="card-sub">Téléchargez les logs et le journal de traçabilité du système.</div>
            </div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <a href="{{ route('admin.logs.download.pdf') }}" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 6px; background: transparent; color: #ef4444; border: 1px solid #ef4444; text-decoration: none; font-weight: 500;">
                <i class="fas fa-file-pdf"></i> Télécharger en PDF
            </a>
            <a href="{{ route('admin.logs.download.word') }}" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 6px; background: transparent; color: #0ea5e9; border: 1px solid #0ea5e9; text-decoration: none; font-weight: 500;">
                <i class="fas fa-file-word"></i> Télécharger en Word
            </a>
        </div>
    </div>
</div>

<div class="cards-row" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;margin-right:.5rem;"></i>Alertes prioritaires</div>
                <div class="card-sub">Documents a signer (signature finale).</div>
            </div>
        </div>
        @php $showAlertes = isset($alertes) && $alertes->count() > 0; @endphp
        @if($showAlertes)
            <div style="display:grid;gap:.4rem;">
                @forelse($alertes as $doc)
                    @php
                        $isUrgent = $doc->deadline && $doc->deadline->isPast();
                        $isWarning = !$isUrgent && $doc->deadline && $doc->deadline->isBefore(now()->addDays(2));
                        $badgeClass = $isUrgent ? 'badge-danger' : ($isWarning ? 'badge-warning' : 'badge-info');
                    @endphp
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
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Telecharger</a>
                            <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openSignModal('{{ $doc->id }}')">Signer (Final)</button>
                        </div>
                    </div>
                @empty
                    <div style="color:var(--muted);padding:1rem;text-align:center;">
                        <i class="fas fa-check-circle fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                        Aucune signature finale en attente.
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="fas fa-eye" style="color:#38bdf8;margin-right:.5rem;"></i>Documents a superviser</div>
                <div class="card-sub">Documents en cours de traitement.</div>
            </div>
        </div>
        @php $showSupervision = isset($documentsSupervision) && $documentsSupervision->count() > 0; @endphp
        @if($showSupervision)
            <div style="display:grid;gap:.4rem;">
                @forelse($documentsSupervision as $doc)
                    @php
                        $isUrgent = $doc->deadline && $doc->deadline->isPast();
                        $isWarning = !$isUrgent && $doc->deadline && $doc->deadline->isBefore(now()->addDays(2));
                        $badgeClass = $isUrgent ? 'badge-danger' : ($isWarning ? 'badge-warning' : 'badge-info');
                    @endphp
                    <div style="padding:.6rem;border-radius:.4rem;background:rgba({{ $isUrgent ? '239,68,68' : ($isWarning ? '245,158,11' : '56,189,248') }},0.1);border-left:3px solid {{ $isUrgent ? 'var(--danger)' : ($isWarning ? 'var(--warning)' : 'var(--info)') }};">
                        <div style="display:flex;justify-content:space-between;align-items:start;gap:.75rem;margin-bottom:.3rem;">
                            <div>
                                <div style="font-weight:600;font-size:.85rem;">{{ $doc->name }}</div>
                                <div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
                                    {{ $doc->code ?: 'Sans code' }} | {{ $doc->status }}
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
                    </div>
                @empty
                    <div style="color:var(--muted);padding:1rem;text-align:center;">
                        <i class="fas fa-check-circle fa-2x" style="display:block;margin-bottom:.75rem;opacity:.3;"></i>
                        Aucun document a superviser.
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</div>

<div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Repartition des roles</div>
                <div class="card-sub">Vue rapide des profils actifs.</div>
            </div>
        </div>
        <div style="display: grid; gap: 8px;">
            <div style="display: flex; justify-content: space-between; padding: 8px; background: rgba(56,189,248,0.15); border-radius: 4px;">
                <span><i class="fas fa-pen"></i> Createurs</span>
                <span style="font-weight: 700; color: #38bdf8;">{{ $usersByRole['creator'] ?? 0 }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px; background: rgba(245,158,11,0.15); border-radius: 4px;">
                <span><i class="fas fa-check"></i> Validateurs</span>
                <span style="font-weight: 700; color: #f59e0b;">{{ $usersByRole['validator'] ?? 0 }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px; background: rgba(34,197,94,0.15); border-radius: 4px;">
                <span><i class="fas fa-signature"></i> Approbateurs</span>
                <span style="font-weight: 700; color: #22c55e;">{{ $usersByRole['approver'] ?? 0 }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px; background: rgba(156,163,175,0.15); border-radius: 4px; border: 1px solid rgba(156,163,175,0.3);">
                <span><i class="fas fa-users"></i> Total roles actifs</span>
                <span style="font-weight: 700; color: #9ca3af;">{{ ($usersByRole['creator'] ?? 0) + ($usersByRole['validator'] ?? 0) + ($usersByRole['approver'] ?? 0) }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 8px; background: rgba(239,68,68,0.15); border-radius: 4px;">
                <span><i class="fas fa-cog"></i> Admins</span>
                <span style="font-weight: 700; color: #ef4444;">{{ $usersByRole['admin'] ?? 0 }}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
        <div>
            <div style="font-size:1rem;font-weight:700;">Supervision globale</div>
            <div style="font-size:.76rem;color:var(--muted);margin-top:.15rem;">Tous les documents du systeme selon la periode selectionnee.</div>
        </div>
        <form method="GET" style="display:flex;align-items:center;gap:8px;margin:0;">
            <select name="range" onchange="this.form.submit()" style="padding:6px;border-radius:4px;background:#1f2937;color:#e5e7eb;border:1px solid #374151;">
                <option value="week" @selected($range==='week')>7 jours</option>
                <option value="month" @selected($range==='month')>30 jours</option>
                <option value="year" @selected($range==='year')>1 an</option>
            </select>
        </form>
    </div>
    <div style="position:relative;">
            <table style="position:relative;">
            <thead>
            <tr>
                <th class="col-nom">Nom</th>
                <th class="col-code">Code</th>
                <th class="col-type">Type</th>
                <th class="col-createur">Createur</th>
                <th class="col-aio">AIO</th>
                <th class="col-ligne">Ligne</th>
                <th class="col-phase">Phase</th>
                <th class="col-rev">Rev.</th>
                <th class="col-statut">Statut</th>
                <th class="col-date">Cree le</th>
                <th class="col-valide">Validateur</th>
                <th class="col-approuve">Approbateur</th>
                <th class="col-rejete">Rejete</th>
                <th class="col-signe">Signe</th>
                <th class="col-actions">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($documents as $doc)
<tr>
                    <td class="col-nom" style="font-weight:500;max-width:150px;">
                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $doc->name }}">{{ $doc->name }}</div>
                    </td>
                    <td class="col-code" style="font-family:monospace;font-size:.72rem;">
                        @if($doc->code)
                            <span class="badge bg-secondary">{{ $doc->code }}</span>
                        @else
                            <span class="badge" style="background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.4);">Non code</span>
                        @endif
                    </td>
                    <td class="col-type" style="font-size:.7rem;max-width:110px;" title="{{ $doc->type_libelle }}">{{ Str::limit($doc->type_libelle, 20) }}</td>
                    <td class="col-createur">{{ $doc->creator->name ?? '-' }}</td>
                    <td class="col-aio"><span class="badge badge-info">{{ \App\Models\Document::AIOS[$doc->aio] ?? $doc->aio }}</span></td>
                    <td class="col-ligne">{{ $doc->ligne ?? '-' }}</td>
                    <td class="col-phase">{{ $doc->phase_libelle ?? '-' }}</td>
                    <td class="col-rev" style="font-family:monospace;font-size:.75rem;">{{ $doc->revision }}</td>
                    @php
$st = ['archived' => 'badge-success', 'rejected' => 'badge-danger', 'pending_codification' => 'badge-warning', 'in_validation' => 'badge-info', 'approbation' => 'badge-info', 'validation_admin' => 'badge-info', 'draft' => 'badge-muted'];
$sl = ['archived' => 'Finalisé', 'rejected' => 'Rejeté', 'pending_codification' => 'Codification', 'in_validation' => 'Validation', 'approbation' => 'Approbation', 'validation_admin' => 'A approuver', 'draft' => 'Brouillon'];
$st2 = $st[$doc->status] ?? 'badge-muted';
@endphp
<td class="col-statut"><span class="badge {{ $st2 }}">{{ $sl[$doc->status] ?? $doc->status }}</span></td>
                    <td class="col-date">{{ $doc->created_at->format('d/m/y') }}</td>
                    <td class="col-valide" style="text-align:center;color:var(--muted);">
                        @php
                        $v = $doc->signatures->where('role', 'validator')->first();
                        if (!$v && $doc->validated_by) {
                            $v = ['name' => optional($doc->validatedBy)->name ?? '-', 'signed_at' => $doc->validated_at];
                        }
                        $vName = is_array($v) ? ($v['name'] ?? '-') : ($v->user->name ?? '-');
                        $vDate = is_array($v) ? ($v['signed_at'] ?? null) : ($v->signed_at ?? null);
                        @endphp
                        @if($v)
                        <div>{{ $vName }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $vDate ? $vDate->format('d/m/y') : '-' }}</div>
                        @else
                        -
                        @endif
                    </td>
                    <td class="col-approuve" style="text-align:center;color:var(--muted);">
                        @php
                        $a = $doc->signatures->where('role', 'approver')->first();
                        if (!$a && $doc->approved_by) {
                            $a = ['name' => optional($doc->approvedBy)->name ?? '-', 'signed_at' => $doc->approved_at];
                        }
                        $aName = is_array($a) ? ($a['name'] ?? '-') : ($a->user->name ?? '-');
                        $aDate = is_array($a) ? ($a['signed_at'] ?? null) : ($a->signed_at ?? null);
                        @endphp
                        @if($a)
                        <div>{{ $aName }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $aDate ? $aDate->format('d/m/y') : '-' }}</div>
                        @else
                        -
                        @endif
                    </td>
                    <td class="col-rejete" style="text-align:center;color:var(--muted);">
                        @if($doc->rejected_at)
                        <div>{{ $doc->rejected_by->name ?? '-' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $doc->rejected_at->format('d/m/y') }}</div>
                        @else
                        -
                        @endif
                    </td>
                    <td class="col-signe" style="text-align:center;color:var(--muted);">
                        @if($doc->signed_at)
                        <div>{{ $doc->signed_by->name ?? '-' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $doc->signed_at->format('d/m/y') }}</div>
                        @else
                        -
                        @endif
                    </td>
                    <td class="col-actions">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('documents.download', $doc) }}"><i class="fas fa-code me-2"></i>Télécharger</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.documents.edit', $doc) }}"><i class="fas fa-edit me-2"></i>Modifier</a></li>
                                @if(!$doc->is_fully_signed)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button"
                                                onclick="openGlobalDeleteModal('{{ route('admin.documents.destroy', $doc) }}', '{{ $doc->nom ?? $doc->name ?? '' }}', 'Supprimer le document')"
                                                class="dropdown-item" style="color:#ef4444;"><i class="fas fa-trash me-2"></i>Supprimer</button>
                                    </li>
                                @endif
                                @if($doc->status==='validation_admin' && $doc->current_role==='admin')
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.workflow.validate', $doc) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="color:#22c55e;"><i class="fas fa-check me-2"></i>Valider</button>
                                        </form>
                                    </li>
                                    <li>
                                         <a class="dropdown-item text-danger"
                                            href="#"
                                            onclick="event.preventDefault(); openRejectModal('{{ route('admin.workflow.reject', $doc) }}')">
                                             <i class="fas fa-times me-2"></i>Rejeter
                                         </a>
                                     </li>
                                 @endif
                                 @if($doc->status==='signing_admin' && $doc->current_role==='admin')
                                     <li><hr class="dropdown-divider"></li>
                                     <li>
                                         <button type="button" class="dropdown-item" style="color:#c084fc;cursor:pointer;" onclick="openSign('{{ $doc->id }}')">
                                             <i class="fas fa-signature me-2"></i>Signer (PDF final)
                                         </button>
                                     </li>
                                     <li>
                                         <a class="dropdown-item text-danger"
                                            href="#"
                                            onclick="event.preventDefault(); openRejectModal('{{ route('admin.workflow.reject', $doc) }}')">
                                             <i class="fas fa-times me-2"></i>Rejeter
                                         </a>
                                     </li>
                                 @endif
                                @if($doc->is_fully_signed)
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="{{ route('admin.documents.export.pdf', $doc) }}"><i class="fas fa-file-pdf me-2"></i>PDF</a></li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty<tr><td colspan="15" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun document.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $documents->links() }}</div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title"><i class="fas fa-history" style="color:#f59e0b;margin-right:.5rem;"></i>Journaux recents</div>
            <div class="card-sub">Dernieres actions tracees.</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Cible</th></tr></thead>
            <tbody>
            @forelse($recentLogs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? 'Systeme' }}</td>
                    <td>
                        @php
                        $actionColors = [
                            'login' => '#22c55e',
                            'logout' => '#ef4444',
                            'document_submitted' => '#3b82f6',
                            'submitted_to_admin' => '#1d4ed8',
                            'document_approved' => '#22c55e',
                            'user_approved' => '#8b5cf6',
                            'code_assigned' => '#f59e0b',
                            'document_rejected' => '#ef4444',
                        ];
                        $color = $actionColors[strtolower($log->action)] ?? '#6b7280';
                        @endphp
                        <span class="badge" style="background-color:{{ $color }};">{{ strtoupper($log->action) }}</span>
                    </td>
                    <td style="font-size:.72rem;">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                </tr>
        @empty<tr><td colspan="4" style="text-align:center;color:var(--muted);">Aucun journal.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<!-- Sign Modal -->
<div id="signModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.75);z-index:9999;align-items:flex-start;justify-content:center;overflow-y:auto;padding:20px 0;">
  <div style="background:#0f172a;border:1px solid #22c55e;border-radius:16px;padding:2rem;max-width:480px;width:90%;margin:20px auto;box-shadow:0 25px 60px rgba(34,197,94,0.2);max-height:calc(100vh - 40px);overflow-y:auto;position:relative;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid #1e293b;">
      <div style="width:42px;height:42px;background:rgba(34,197,94,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">✍️</div>
      <div>
        <h5 style="color:white;font-weight:700;margin:0;font-size:1.1rem;">Signature finale</h5>
        <p style="color:#64748b;margin:0;font-size:0.8rem;">Uploadez le PDF signé final pour archiver le document</p>
      </div>
      <button onclick="closeSignModal()" style="margin-left:auto;background:none;border:none;color:#64748b;font-size:1.2rem;cursor:pointer;padding:4px 8px;border-radius:6px;">✕</button>
    </div>
    <form id="signForm" method="POST" enctype="multipart/form-data">
      @csrf
      <div style="margin-bottom:1.2rem;">
        <label style="color:#94a3b8;font-size:0.78rem;font-weight:600;letter-spacing:0.05em;display:block;margin-bottom:6px;">PDF SIGNÉ FINAL <span style="color:#ef4444;">*</span></label>
        <input type="file" name="document_signe" accept=".pdf" required
          style="width:100%;background:#1e293b;border:1px solid #334155;border-radius:10px;color:white;padding:12px;font-size:0.9rem;outline:none;box-sizing:border-box;transition:border-color 0.2s;color-scheme:dark;"
          onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor='#334155'">
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <button type="button" onclick="closeSignModal()"
          style="padding:10px 24px;background:#1e293b;color:#94a3b8;border:1px solid #334155;border-radius:10px;cursor:pointer;font-size:0.9rem;transition:all 0.2s;"
          onmouseover="this.style.background='#334155'" onmouseout="this.style.background='#1e293b'">
          Annuler
        </button>
        <button type="submit"
          style="padding:10px 24px;background:#22c55e;color:white;border:none;border-radius:10px;cursor:pointer;font-size:0.9rem;font-weight:600;transition:all 0.2s;"
          onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
          Signer et archiver
        </button>
      </div>
    </form>
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

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function openSignModal(id){
    var modal = document.getElementById('signModal');
    if (modal) {
        document.getElementById('signForm').action = '/admin/workflow/' + id + '/sign';
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function openSign(id){
    openSignModal(id);
}

function closeSignModal() {
    document.getElementById('signModal').style.display = 'none';
    document.body.style.overflow = '';
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
document.addEventListener('DOMContentLoaded', function() {
    var signModal = document.getElementById('signModal');
    if (signModal) {
        signModal.addEventListener('click', function(e) {
            if (e.target === this) closeSignModal();
        });
    }
    var rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    }
});

const ctx=document.getElementById('activityChart');
if(ctx){
    const chartData = @json($activityChart);
    const labels = chartData.labels || [];
    const created = chartData.created || [];
    const validated = chartData.validated || [];
    const rejected = chartData.rejected || [];
    new Chart(ctx.getContext('2d'),{type:'bar',data:{labels:labels,datasets:[{label:'Créé',data:created,backgroundColor:'#38bdf8'},{label:'Validé',data:validated,backgroundColor:'#22c55e'},{label:'Rejeté',data:rejected,backgroundColor:'#ef4444'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true}}}});
}
</script>
@endsection




