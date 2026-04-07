@extends('layouts.app')

@section('title', 'Documents a valider')

@section('content')
<div class="cards-row">
    <div class="stat-card">
        <div class="stat-label">En attente</div>
        <div class="stat-value">{{ $stats['pending'] }}</div>
        <div class="stat-meta">Documents a traiter maintenant</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Valides</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['processed'] }}</div>
        <div class="stat-meta">Historique personnel</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Rejetes</div>
        <div class="stat-value" style="color:#f87171;">{{ $stats['rejected'] }}</div>
        <div class="stat-meta">Documents retournes au createur</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Notifications</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $stats['notifications'] }}</div>
        <div class="stat-meta">Non lues</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Documents a verifier</div>
            <div class="card-sub">Documents signes par le createur et en attente de votre validation.</div>
        </div>
        <span class="badge badge-warning" style="font-size:.8rem;padding:.3rem .7rem;">
            {{ $documents->total() }} document(s)
        </span>
    </div>

    @if($documents->isEmpty())
        <div style="text-align:center;padding:2rem;color:var(--muted);">
            <div>Aucun document en attente de verification.</div>
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
                                <form method="POST" action="{{ route('workflow.checker.validate', $doc) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="border-color:rgba(34,197,94,0.5);">Valider</button>
                                </form>
                                <button type="button" class="btn btn-ghost btn-sm btn-danger" onclick="toggleReject('rej-{{ $doc->id }}')">Rejeter</button>
                            </div>
                            <div id="rej-{{ $doc->id }}" style="display:none;margin-top:.5rem;">
                                <form method="POST" action="{{ route('workflow.checker.reject', $doc) }}" style="display:flex;flex-direction:column;gap:.4rem;">
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

    <div class="card">
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
</script>
@endsection