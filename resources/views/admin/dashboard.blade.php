@extends('layouts.app')

@section('title', 'Dashboard Admin')

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
    <a href="{{ route('admin.documents.index', ['status' => 'finalized', 'range' => $range]) }}" class="stat-card" style="position: relative;">
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

<div class="cards-row">
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
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('admin.documents.codification') }}" style="width: 50%; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 6px; background: transparent; color: #9ca3af; border: 1px solid #6b7280; text-decoration: none;">
                    <i class="fas fa-code"></i> Codification
                </a>
                <div style="width: 50%; display: flex; gap: 8px;">
                    <a href="{{ route('admin.documents.export.follow-up-pdf') }}" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 6px; background: transparent; color: #ef4444; border: 1px solid #ef4444; text-decoration: none;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('admin.documents.export.follow-up-word') }}" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 6px; background: transparent; color: #38bdf8; border: 1px solid #38bdf8; text-decoration: none;">
                        <i class="fas fa-file-word"></i> Word
                    </a>
                </div>
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
                                    {{ $doc->code ?: 'Sans code' }} | Par {{ $doc->creator->name ?? 'Inconnu' }} | v{{ $doc->revision }}
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
                    <td class="col-createur">{{ $doc->creator->name ?? '—' }}</td>
                    <td class="col-aio"><span class="badge badge-info">{{ \App\Models\Document::AIOS[$doc->aio] ?? $doc->aio }}</span></td>
                    <td class="col-ligne">{{ $doc->ligne ?? '—' }}</td>
                    <td class="col-phase">{{ $doc->phase_libelle ?? '—' }}</td>
                    <td class="col-rev" style="font-family:monospace;font-size:.75rem;">v{{ $doc->revision }}</td>
                    @php
$st = ['finalized' => 'badge-success', 'rejected' => 'badge-danger', 'pending_codification' => 'badge-warning', 'in_validation' => 'badge-info', 'approbation' => 'badge-info', 'validation_admin' => 'badge-info', 'draft' => 'badge-muted'];
$sl = ['finalized' => 'Finalise', 'rejected' => 'Rejete', 'pending_codification' => 'Codification', 'in_validation' => 'Validation', 'approbation' => 'Approbation', 'validation_admin' => 'A approuver', 'draft' => 'Brouillon'];
$st2 = $st[$doc->status] ?? 'badge-muted';
@endphp
<td class="col-statut"><span class="badge {{ $st2 }}">{{ $sl[$doc->status] ?? $doc->status }}</span></td>
                    <td class="col-date">{{ $doc->created_at->format('d/m/y') }}</td>
                    <td class="col-valide" style="text-align:center;color:var(--muted);">
                        @php
                        $v = $doc->signatures->where('role', 'validator')->first();
                        if (!$v && $doc->validated_by) {
                            $v = ['name' => optional($doc->validatedBy)->name ?? '—', 'signed_at' => $doc->validated_at];
                        }
                        $vName = is_array($v) ? ($v['name'] ?? '—') : ($v->user->name ?? '—');
                        $vDate = is_array($v) ? ($v['signed_at'] ?? null) : ($v->signed_at ?? null);
                        @endphp
                        @if($v)
                        <div>{{ $vName }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $vDate ? $vDate->format('d/m/y') : '—' }}</div>
                        @else
                        —
                        @endif
                    </td>
                    <td class="col-approuve" style="text-align:center;color:var(--muted);">
                        @php
                        $a = $doc->signatures->where('role', 'approver')->first();
                        if (!$a && $doc->approved_by) {
                            $a = ['name' => optional($doc->approvedBy)->name ?? '—', 'signed_at' => $doc->approved_at];
                        }
                        $aName = is_array($a) ? ($a['name'] ?? '—') : ($a->user->name ?? '—');
                        $aDate = is_array($a) ? ($a['signed_at'] ?? null) : ($a->signed_at ?? null);
                        @endphp
                        @if($a)
                        <div>{{ $aName }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $aDate ? $aDate->format('d/m/y') : '—' }}</div>
                        @else
                        —
                        @endif
                    </td>
                    <td class="col-rejete" style="text-align:center;color:var(--muted);">
                        @if($doc->rejected_at)
                        <div>{{ $doc->rejected_by->name ?? '—' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $doc->rejected_at->format('d/m/y') }}</div>
                        @else
                        —
                        @endif
                    </td>
                    <td class="col-signe" style="text-align:center;color:var(--muted);">
                        @if($doc->signed_at)
                        <div>{{ $doc->signed_by->name ?? '—' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $doc->signed_at->format('d/m/y') }}</div>
                        @else
                        —
                        @endif
                    </td>
                    <td class="col-actions">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('documents.download', $doc) }}"><i class="fas fa-code me-2"></i>Source</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.documents.edit', $doc) }}"><i class="fas fa-edit me-2"></i>Modifier</a></li>
                                @if(!$doc->is_fully_signed)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" onsubmit="return confirm('Supprimer ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item" style="color:#ef4444;"><i class="fas fa-trash me-2"></i>Supprimer</button>
                                        </form>
                                    </li>
                                @endif
                                @if($doc->status==='validation_admin' && $doc->current_role==='admin')
                                    <li><hr class="dropdown-divider"></li>
                                    @php $h=$doc->signatures->where('role','admin')->isNotEmpty(); @endphp
                                    @if($h)
                                        <li><button type="button" class="dropdown-item" style="color:#22c55e;cursor:pointer;" onclick="openSign('{{ $doc->id }}')"><i class="fas fa-signature me-2"></i>Signer</button></li>
                                    @else
                                        <li>
                                            <form method="POST" action="{{ route('admin.workflow.validate', $doc) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item" style="color:#22c55e;"><i class="fas fa-check me-2"></i>Valider</button>
                                            </form>
                                        </li>
                                    @endif
                                    <li>
                                        <a class="dropdown-item text-danger"
                                           href="#"
                                           data-bs-toggle="modal"
                                           data-bs-target="#modalRejet{{ $doc->id }}">
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
</div>

@foreach($documents as $doc)
@if(in_array($doc->status, ['in_validation', 'signing_admin']) && $doc->current_role==='admin')
<div class="modal fade" id="modalRejet{{ $doc->id }}">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#1a2035;color:white;">
            <div class="modal-header">
                <h5>Rejeter : {{ $doc->name }}</h5>
                <button data-bs-dismiss="modal" class="btn-close btn-close-white"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('admin.workflow.reject', $doc) }}">
                    @csrf
                    <div class="mb-3">
                        <label>Motif du rejet *</label>
                        <textarea name="message" required rows="3" class="form-control mt-1" style="background:#0f172a;color:white;" placeholder="Raison du rejet..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Deadline de correction *</label>
                        <input type="datetime-local" name="deadline" required class="form-control mt-1" style="background:#0f172a;color:white;">
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Confirmer le rejet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div id="signModal-{{$doc->id}}" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#1a2035;border-radius:8px;padding:24px;max-width:500px;width:90%;border:1px solid rgba(255,255,255,0.1);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;color:white;">✍️ Signer le document</h3>
            <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('signModal-{{$doc->id}}').style.display='none'"></button>
        </div>
        <form method="POST" action="{{ route('admin.workflow.sign', $doc) }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3 p-3 rounded" style="background:rgba(255,255,255,0.05);">
                <small class="text-muted">Document :</small>
                <p class="mb-0 fw-bold">{{ $doc->name }}</p>
                <small class="text-muted">Code : {{ $doc->code }}</small>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">📎 Televerser le document signe *</label>
                <input type="file" name="document_signe" accept=".pdf" required style="width:100%;padding:12px;border:1px solid #374151;border-radius:4px;background:#0f172a;color:#e5e7eb;">
                <small class="text-muted">Format accepte : PDF</small>
            </div>
            <div class="mb-3">
                <label class="form-label">💬 Commentaire (optionnel)</label>
                <textarea name="commentaire" rows="3" style="width:100%;padding:12px;border:1px solid #374151;border-radius:4px;background:#0f172a;color:#e5e7eb;" placeholder="Ajouter un commentaire..."></textarea>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('signModal-{{$doc->id}}').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-primary">Signer et finaliser</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

@foreach($alertes as $doc)
    @if(!$documents->contains('id', $doc->id))
        <div id="signModal-{{$doc->id}}" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;">
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#1a2035;border-radius:8px;padding:24px;max-width:500px;width:90%;border:1px solid rgba(255,255,255,0.1);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <h3 style="margin:0;color:white;">✍️ Signer le document</h3>
                    <button type="button" class="btn-close btn-close-white" onclick="document.getElementById('signModal-{{$doc->id}}').style.display='none'"></button>
                </div>
                <form method="POST" action="{{ route('admin.workflow.sign', $doc) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3 p-3 rounded" style="background:rgba(255,255,255,0.05);">
                        <small class="text-muted">Document :</small>
                        <p class="mb-0 fw-bold">{{ $doc->name }}</p>
                        <small class="text-muted">Code : {{ $doc->code }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">📎 Televerser le document signe *</label>
                        <input type="file" name="document_signe" accept=".pdf" required style="width:100%;padding:12px;border:1px solid #374151;border-radius:4px;background:#0f172a;color:#e5e7eb;">
                        <small class="text-muted">Format accepte : PDF</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">💬 Commentaire (optionnel)</label>
                        <textarea name="commentaire" rows="3" style="width:100%;padding:12px;border:1px solid #374151;border-radius:4px;background:#0f172a;color:#e5e7eb;" placeholder="Ajouter un commentaire..."></textarea>
                    </div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('signModal-{{$doc->id}}').style.display='none'">Annuler</button>
                        <button type="submit" class="btn btn-primary">Signer et finaliser</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function openSignModal(id){
    var modal = document.getElementById('signModal-' + id);
    if (modal) {
        modal.style.display = 'block';
    }
}

function openSign(id){
    openSignModal(id);
}
const ctx=document.getElementById('activityChart');
if(ctx){
    const chartData = @json($activityChart);
    const labels = chartData.labels || [];
    const created = chartData.created || [];
    const validated = chartData.validated || [];
    const rejected = chartData.rejected || [];
    new Chart(ctx.getContext('2d'),{type:'bar',data:{labels:labels,datasets:[{label:'Cree',data:created,backgroundColor:'#38bdf8'},{label:'Valide',data:validated,backgroundColor:'#22c55e'},{label:'Rejete',data:rejected,backgroundColor:'#ef4444'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true}}}});
}
</script>
@endsection
