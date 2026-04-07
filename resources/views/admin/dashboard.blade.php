@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="cards-row" style="margin-bottom:1.25rem;">
    <div class="stat-card">
        <div class="stat-label">Documents crees</div>
        <div class="stat-value">{{ $createdCount }}</div>
        <div class="stat-meta">Periode : {{ $range === 'week' ? '7 jours' : ($range === 'month' ? '30 jours' : '1 an') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">En codification</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $pendingCodification }}</div>
        <div class="stat-meta">En attente chez l admin</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">En validation</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $inValidationCount }}</div>
        <div class="stat-meta">Workflow en cours</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Finalises</div>
        <div class="stat-value" style="color:#4ade80;">{{ $finalizedCount }}</div>
        <div class="stat-meta">Signes et archives</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Rejetes</div>
        <div class="stat-value" style="color:#f87171;">{{ $rejectedCount }}</div>
        <div class="stat-meta">Retournes au createur</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Utilisateurs</div>
        <div class="stat-value">{{ $totalUsers }}</div>
        <div class="stat-meta">Tous roles confondus</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Comptes en attente</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $pendingUsers }}</div>
        <div class="stat-meta">Validation admin requise</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Codes en attente</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $awaitingVerification }}</div>
        <div class="stat-meta">Utilisateurs approuves non verifies</div>
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
        <div style="display:grid;gap:.6rem;">
            <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Ouvrir la gestion des utilisateurs</a>
            <a href="{{ route('admin.users.pending') }}" class="btn btn-ghost">Traiter les comptes en attente</a>
            <a href="{{ route('admin.documents.codification') }}" class="btn btn-ghost">Ouvrir la codification</a>
            <a href="{{ route('admin.documents.export.csv') }}" class="btn btn-ghost">Exporter le suivi CSV</a>
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
                <strong>{{ $usersByRole['checker'] ?? 0 }}</strong>
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
                            @if($doc->status === 'in_validation' && $doc->current_role === 'admin')
                                <form method="POST" action="{{ route('admin.workflow.sign', $doc) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm">Signer</button>
                                </form>
                            @endif
                            @if($doc->is_fully_signed)
                                <a href="{{ route('admin.documents.export.pdf', $doc) }}" class="btn btn-ghost btn-sm">PDF</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun document sur cette periode.</td>
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
@endsection
