@extends('layouts.app')

@section('title', 'Documents a valider')

@section('content')
<div class="cards-row">
    <a href="{{ route('workflow.validator.index') }}" class="stat-card {{ !$filter || $filter === 'pending' ? 'active' : '' }}">
        <div class="stat-label">En attente</div>
        <div class="stat-value">{{ $stats['pending'] }}</div>
        <div class="stat-meta">Documents a traiter maintenant</div>
    </a>
    <a href="{{ route('workflow.validator.index', ['filter' => 'processed']) }}" class="stat-card {{ $filter === 'processed' ? 'active' : '' }}">
        <div class="stat-label">Valides</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['processed'] }}</div>
        <div class="stat-meta">Historique personnel</div>
    </a>
    <a href="{{ route('workflow.validator.index', ['filter' => 'rejected']) }}" class="stat-card {{ $filter === 'rejected' ? 'active' : '' }}">
        <div class="stat-label">Rejetes</div>
        <div class="stat-value" style="color:#f87171;">{{ $stats['rejected'] }}</div>
        <div class="stat-meta">Documents retournes au createur</div>
    </a>
    <a href="#notifications" class="stat-card">
        <div class="stat-label">Notifications</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $stats['notifications'] }}</div>
        <div class="stat-meta">Non lues</div>
    </a>
</div>

<div class="card" id="recent-alerts">
    <div class="card-header">
        <div>
            <div class="card-title">Vos dernieres alertes documentaires</div>
            <div class="card-sub">Documents prioritaires a traiter en priorite.</div>
        </div>
    </div>
    @php
        $urgentDocuments = null;
        if (!$filter || $filter === 'pending') {
            $urgentDocuments = \App\Models\Document::where('status', 'in_validation')
                ->where('current_role', 'validator')
                ->whereNotNull('deadline')
                ->orderByRaw('CASE
                    WHEN deadline < NOW() THEN 0
                    WHEN deadline < DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1
                    ELSE 2
                END')
                ->limit(5)
                ->get();
        }
    @endphp

    @if($urgentDocuments && $urgentDocuments->count() > 0)
        <div style="display:grid;gap:.4rem;">
            @foreach($urgentDocuments as $doc)
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
                                INFO
                            @endif
                        </span>
                    </div>
                    <div style="font-size:.75rem;color:var(--muted);margin-bottom:.4rem;">
                        Deadline: <strong style="font-family:monospace;{{ $isUrgent ? 'color:var(--danger)' : '' }}">{{ $doc->deadline ? $doc->deadline->format('d/m/Y H:i') : 'N/A' }}</strong>
                        @if($doc->deadline && !$isUrgent && !$isWarning)
                            (dans {{ $doc->deadline->diffInDays(now()) }} jour(s))
                        @elseif($isUrgent)
                            (dépassée de {{ now()->diffInDays($doc->deadline, false) }} jour(s))
                        @endif
                    </div>
                    <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                        <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" style="font-size:.72rem;">Télécharger</a>
                        <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);font-size:.72rem;" onclick="openValidateModal('{{ $doc->id }}')">Signer</button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div style="color:var(--muted);padding:.6rem;text-align:center;">
            Aucun document critique en attente.
        </div>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">
                @if($filter === 'processed')
                    Documents valides
                @elseif($filter === 'rejected')
                    Documents rejetes
                @else
                    Documents a verifier
                @endif
            </div>
            <div class="card-sub">
                @if($filter === 'processed')
                    Documents que vous avez signes comme validateur
                @elseif($filter === 'rejected')
                    Documents que vous avez retournes au createur
                @else
                    Documents signes par le createur et en attente de votre validation
                @endif
            </div>
        </div>
        <span class="badge badge-warning" style="font-size:.8rem;padding:.3rem .7rem;">
            {{ $documents->total() }} document(s)
        </span>
    </div>

    @if($documents->isEmpty())
        <div style="text-align:center;padding:2rem;color:var(--muted);">
            <div>
                @if($filter === 'processed')
                    Aucun document valide.
                @elseif($filter === 'rejected')
                    Aucun document rejete.
                @else
                    Aucun document en attente de verification.
                @endif
            </div>
        </div>
    @else
        <div style="overflow-x:auto;margin-top:.75rem;">
            <table>
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
                        <td style="font-family:monospace;font-size:.73rem;color:var(--accent);">{{ $doc->code }}</td>
                        <td style="font-size:.72rem;max-width:120px;" title="{{ $doc->type_libelle }}">
                            {{ \Illuminate\Support\Str::limit($doc->type_libelle, 22) }}
                        </td>
                        <td><span class="badge badge-info">{{ \App\Models\Document::AIOS[$doc->aio] ?? $doc->aio }}</span></td>
                        <td>{{ $doc->ligne }}</td>
                        <td style="font-size:.72rem;">{{ $doc->phase_libelle }}</td>
                        <td style="font-family:monospace;font-size:.75rem;">v{{ $doc->revision }}</td>
                        <td>{{ $doc->creator->name ?? '—' }} {{ $doc->creator->prenom ?? '' }}</td>
                        <td style="font-size:.72rem;">
                            @if($doc->deadline)
                                <span style="{{ $doc->deadline->isPast() ? 'color:var(--danger)' : '' }}">
                                    {{ $doc->deadline->format('d/m/Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
                                <a href="{{ route('documents.download', $doc) }}" class="btn btn-ghost btn-sm" title="Telecharger">Source</a>
                                <button type="button" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);" onclick="openValidateModal('{{ $doc->id }}')">Signer</button>
                                <button type="button" class="btn btn-ghost btn-sm btn-danger" onclick="toggleReject('rej-{{ $doc->id }}')">Rejeter</button>
                            </div>
                            <div id="rej-{{ $doc->id }}" style="display:none;margin-top:.5rem;">
                                <form method="POST" action="{{ route('workflow.validator.reject', $doc) }}" style="display:flex;flex-direction:column;gap:.4rem;">
                                    @csrf
                                    <textarea name="message" placeholder="Motif du rejet" required style="font-size:.78rem;padding:.4rem;border-radius:.4rem;border:1px solid rgba(239,68,68,0.4);background:#020617;color:#e5e7eb;min-height:70px;resize:vertical;"></textarea>
                                    <label style="font-size:.72rem;color:var(--muted);">Nouvelle deadline</label>
                                    <input type="datetime-local" name="deadline" value="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}" required style="font-size:.78rem;padding:.3rem .5rem;border-radius:.4rem;border:1px solid var(--border);background:#020617;color:#e5e7eb;">
                                    <div style="display:flex;gap:.3rem;">
                                        <button type="submit" class="btn btn-danger btn-sm">Confirmer</button>
                                        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleReject('rej-{{ $doc->id }}')">Annuler</button>
                                    </div>
                                </form>
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



<!-- Modal de signature -->
@foreach($documents as $doc)
<div id="validate-modal-{{ $doc->id }}" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#020617;border-radius:.5rem;padding:1.5rem;max-width:400px;width:90%;">
        <h3 style="margin:0 0 1rem 0;color:#e5e7eb;">Signer le document</h3>
        <p style="margin:0 0 1rem 0;font-size:.9rem;color:#9ca3af;">Téléchargez le document, appliquez votre signature numérique, puis uploadez le fichier signé.</p>
        <div style="display:flex;gap:.5rem;margin-bottom:1rem;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="downloadDocument('{{ $doc->id }}')">Télécharger</button>
        </div>
        <form method="POST" action="{{ route('workflow.validator.validate', $doc) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="signed_file" accept=".pdf,.docx,.xlsx" required style="width:100%;padding:.5rem;border:1px solid var(--border);border-radius:.25rem;background:#020617;color:#e5e7eb;">
            <div style="display:flex;gap:.5rem;margin-top:1rem;">
                <button type="submit" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);">Signer</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="closeValidateModal('{{ $doc->id }}')">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="cards-row">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Historique recent</div>
                <div class="card-sub">Derniers documents que vous avez deja valides.</div>
            </div>
        </div>
        @forelse($processedDocuments as $doc)
            <div style="padding:.55rem 0;border-bottom:1px solid rgba(31,41,55,0.8);">
                <div style="font-weight:600;">{{ $doc->name }}</div>
                <div style="font-size:.74rem;color:var(--muted);">{{ $doc->code ?: 'Sans code' }} | v{{ $doc->revision }}</div>
            </div>
        @empty
            <div style="color:var(--muted);">Aucun document traite pour le moment.</div>
        @endforelse
    </div>

    <div class="card" id="notifications">
        <div class="card-header">
            <div>
                <div class="card-title">Notifications</div>
                <div class="card-sub">Vos dernieres alertes documentaires.</div>
            </div>
        </div>
        @forelse($notifications as $notification)
            <div style="padding:.55rem 0;border-bottom:1px solid rgba(31,41,55,0.8);">
                <div style="font-size:.78rem;">{{ $notification->data['message'] ?? ($notification->data['type'] ?? 'Notification') }}</div>
                <div style="font-size:.72rem;color:var(--muted);">{{ $notification->created_at->format('d/m/Y H:i') }}</div>
            </div>
        @empty
            <div style="color:var(--muted);">Aucune notification pour le moment.</div>
        @endforelse
    </div>
</div>

<script>
function toggleReject(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function openValidateModal(id) {
    document.getElementById('validate-modal-' + id).style.display = 'block';
}

function closeValidateModal(id) {
    document.getElementById('validate-modal-' + id).style.display = 'none';
}

function downloadDocument(id) {
    window.open('/documents/' + id + '/download', '_blank');
}
</script>
@endsection
