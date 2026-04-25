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
    <div class="card-header">
        <div>
            <div class="card-title">Supervision globale</div>
            <div class="card-sub">Tous les documents du systeme selon la periode selectionnee.</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <form method="GET" style="display:flex;align-items:center;gap:8px;margin:0;">
                <select name="range" onchange="this.form.submit()" style="padding: 6px; border-radius: 4px; background: #1f2937; color: #e5e7eb; border: 1px solid #374151;">
                    <option value="week" @selected($range==='week')>7 jours</option>
                    <option value="month" @selected($range==='month')>30 jours</option>
                    <option value="year" @selected($range==='year')>1 an</option>
                </select>
            </form>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>Nom</th><th>Code</th><th>Type</th>
                <th class="col-createur">Createur</th>
                <th class="col-aio">AIO</th>
                <th class="col-ligne">Ligne</th>
                <th class="col-phase">Phase</th>
                <th class="col-rev">Rev.</th>
                <th>Role actuel</th><th>Statut</th><th>Cree le</th>
                <th class="col-valide">Validateur</th>
                <th class="col-approuve">Approbateur</th>
                <th class="col-rejete">Rejete le</th>
                <th class="col-signe">Signe</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($documents as $doc)
                <tr>
                    <td style="font-weight:500;max-width:150px;">
                        <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $doc->name }}">{{ $doc->name }}</div>
                    </td>
                    <td style="font-family:monospace;font-size:.72rem;color:var(--accent);">{{ $doc->code ?? '-' }}</td>
                    <td style="font-size:.7rem;max-width:110px;">{{ \Illuminate\Support\Str::limit($doc->type_libelle, 20) }}</td>
                    <td class="col-createur">{{ $doc->creator->name ?? '' }}</td>
                    <td class="col-aio"><span class="badge badge-info">{{ \App\Models\Document::AIOS[$doc->aio] ?? $doc->aio }}</span></td>
                    <td class="col-ligne">{{ $doc->ligne }}</td>
                    <td class="col-phase">{{ $doc->phase }}</td>
                    <td class="col-rev">{{ $doc->revision }}</td>
                    @php
$r = ['createur' => 'badge-info', 'validateur' => 'badge-warning', 'approbateur' => 'badge-primary', 'admin' => 'badge-muted'];
$rt = $r[$doc->role] ?? 'badge-muted';
@endphp
<td><span class="badge {{ $rt }}">{{ $doc->role }}</span></td>
@php
$s = ['finalized' => 'badge-success', 'rejected' => 'badge-danger', 'pending_codification' => 'badge-warning', 'in_validation' => 'badge-info', 'draft' => 'badge-muted'];
$st = $s[$doc->status] ?? 'badge-muted';
$sl = ['finalized' => 'Finalise', 'rejected' => 'Rejete', 'pending_codification' => 'Codification', 'in_validation' => 'Validation', 'draft' => 'Brouillon'];
$sl2 = $sl[$doc->status] ?? $doc->status;
@endphp
<td><span class="badge {{ $st }}">{{ $sl2 }}</span></td>
<td>{{ $doc->created_at->format('d/m/y') }}</td>
                    <td class="col-valide">
                        @php $v = $doc->signatures->where('role', 'validator')->first(); @endphp
                        @if($v)
                        <div>{{ $v->user->name ?? '-' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $v->signed_at->format('d/m/y') }}</div>
                        @endif
                    </td>
                    <td class="col-approuve">
                        @php $a = $doc->signatures->where('role', 'approver')->first(); @endphp
                        @if($a)
                        <div>{{ $a->user->name ?? '-' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $a->signed_at->format('d/m/y') }}</div>
                        @endif
                    </td>
                    <td class="col-rejete">
                        @if($doc->rejected_at)
                        <div>{{ $doc->rejected_by->name ?? '' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $doc->rejected_at->format('d/m/y') }}</div>
                        @endif
                    </td>
                    <td class="col-signe">
                        @if($doc->signed_at)
                        <div>{{ $doc->signed_by->name ?? '' }}</div><div style="font-size:.67rem;color:var(--muted);">{{ $doc->signed_at->format('d/m/y') }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm">Source</a>
                            <a href="{{ route('admin.documents.edit', $doc) }}" class="btn btn-ghost btn-sm">Modifier</a>
                            @if(!$doc->is_fully_signed)<form method="POST" action="{{ route('admin.documents.destroy', $doc) }}" onsubmit="return confirm('Supprimer ?');">@csrf @method('DELETE')<button type="submit" class="btn btn-ghost btn-sm btn-danger">Supprimer</button></form>@endif
                            @if($doc->status==='in_validation' && $doc->current_role==='admin')
                                @php $h=$doc->signatures->where('role','admin')->isNotEmpty(); @endphp
                                @if($h)<button type="button" class="btn btn-sm" onclick="openSign('{{ $doc->id }}')">Signer</button>@else<form method="POST" action="{{ route('admin.workflow.validate', $doc) }}">@csrf<button type="submit" class="btn btn-sm">Valider</button></form>@endif
                                <button type="button" class="btn btn-ghost btn-sm btn-danger" onclick="document.getElementById('rej-{{$doc->id}}').style.display=document.getElementById('rej-{{$doc->id}}').style.display==='none'?'block':'none'">Rejeter</button>
                                <div id="rej-{{$doc->id}}" style="display:none;margin-top:4px;"><form method="POST" action="{{ route('admin.workflow.reject', $doc) }}">@csrf<textarea name="message" placeholder="Motif" required style="width:100%;min-height:50px;"></textarea><button type="submit" class="btn btn-sm btn-danger">Confirmer</button></form></div>
                            @endif
                            @if($doc->is_fully_signed)<a href="{{ route('admin.documents.export.pdf', $doc) }}" class="btn btn-ghost btn-sm">PDF</a>@endif
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
            <div class="card-title">Journaux recents</div>
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
                    <td><span class="badge {{ in_array($log->action,['login','document_validated','user_approved'])?'badge-success':($log->action==='logout'?'badge-danger':'badge-muted') }}">{{ $log->action }}</span></td>
                    <td style="font-size:.72rem;">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td>
                </tr>
            @empty<tr><td colspan="4" style="text-align:center;color:var(--muted);">Aucun journal.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($documents as $doc)
@if($doc->status==='in_validation' && $doc->current_role==='admin')
<div id="sign-{{$doc->id}}" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#0f172a;border-radius:8px;padding:24px;max-width:400px;width:90%;">
        <h3 style="margin:0 0 16px 0;">Signer le document</h3>
        <p style="color:#9ca3af;margin:0 0 16px 0;">Telechargez et verifyez le document.</p>
        <div style="margin-bottom:16px;"><button type="button" class="btn btn-ghost btn-sm" onclick="window.open('/documents/{{$doc->id}}/download')">Telecharger</button></div>
        <form method="POST" action="{{ route('admin.workflow.sign', $doc) }}" enctype="multipart/form-data">@csrf<input type="file" name="signed_file" accept=".pdf" required style="width:100%;padding:12px;border:1px solid #374151;border-radius:4px;background:#1e293b;color:#e5e7eb;"><div style="display:flex;gap:8px;margin-top:16px;"><button type="submit" class="btn btn-sm" style="border-color:#22c55e;">Signer</button><button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('sign-{{$doc->id}}').style.display='none'">Annuler</button></div></form>
    </div>
</div>
@endif
@endforeach
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function openSign(id){document.getElementById('sign-'+id).style.display='block'}
const ctx=document.getElementById('activityChart');
if(ctx){
    const chartData = @json($activityChart);
    const labels = chartData.labels || [];
    const created = chartData.created || [];
    const validated = chartData.validated || [];
    const rejected = chartData.rejected || [];
    new Chart(ctx.getContext('2d'),{type:'bar',data:{labels:labels,datasets:[{label:'Cree',data:created,backgroundColor:'#38bdf8'},{label:'Valide',data:validated,backgroundColor:'#22c55e'},{label:'Rejete',data:rejected,backgroundColor:'#ef4444'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top'}},scales:{y:{beginAtZero:true}}});
}
</script>
@endsection