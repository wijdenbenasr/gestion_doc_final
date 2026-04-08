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
    <a href="{{ route('documents.my', ['status' => 'finalized']) }}" class="stat-card {{ $status === 'finalized' ? 'active' : '' }}">
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
                <a href="{{ route('documents.create') }}" class="btn" style="margin-top:.75rem;">Creer mon premier document</a>
            @endif
        </div>
    @else
        <div style="overflow-x:auto;margin-top:.75rem;">
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
                    <th>Deadline</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td style="font-weight:500;max-width:180px;">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $document->name }}">{{ $document->name }}</div>
                        </td>
                        <td>
                            @if($document->code)
                                <span style="font-family:monospace;font-size:.73rem;color:var(--accent);">{{ $document->code }}</span>
                            @else
                                <span style="color:var(--muted);">-</span>
                            @endif
                        </td>
                        <td style="font-size:.72rem;max-width:140px;" title="{{ $document->type_libelle }}">{{ \Illuminate\Support\Str::limit($document->type_libelle, 28) }}</td>
                        <td><span class="badge badge-info">{{ \App\Models\Document::AIOS[$document->aio] ?? $document->aio }}</span></td>
                        <td>{{ $document->ligne }}</td>
                        <td style="font-size:.72rem;">{{ $document->phase_libelle }}</td>
                        <td style="font-family:monospace;font-size:.75rem;">v{{ $document->revision }}</td>
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
                                    'pending_codification' => ['badge-warning', 'Codification'],
                                    'in_validation' => ['badge-info', 'En validation'],
                                    'ready_for_pdf' => ['badge-success', 'Prêt pour PDF'],
                                    'rejected' => ['badge-danger', 'Rejeté'],
                                    'finalized' => ['badge-success', 'Finalisé'],
                                ];
                                $status = $statusConfig[$document->status] ?? ['badge-muted', $document->status];
                            @endphp
                            <span class="badge {{ $status[0] }}">{{ $status[1] }}</span>
                        </td>
                        <td>
                            <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
                                @if(in_array($document->status, ['draft', 'rejected']) && auth()->user()->role === 'creator')
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-ghost btn-sm">Modifier</a>
                                @endif

                                @if($document->status === 'draft' && empty($document->code) && auth()->user()->role === 'creator')
                                    <form method="POST" action="{{ route('workflow.creator.send', $document) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Envoyer a l admin</button>
                                    </form>
                                @endif

                                @if($document->status === 'draft' && ! empty($document->code) && auth()->user()->role === 'creator')
                                    <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Envoyer pour validation</button>
                                    </form>
                                @endif

                                @if($document->status === 'rejected' && ! empty($document->code) && auth()->user()->role === 'creator')
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-ghost btn-sm">Modifier</a>
                                    <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Renvoyer pour validation</button>
                                    </form>
                                @endif

                                @if($document->status === 'ready_for_pdf' && auth()->user()->role === 'creator')
                                    <form method="POST" action="{{ route('workflow.creator.sign_and_send', $document) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Signer et envoyer</button>
                                    </form>
                                @endif

                                @if($document->status === 'draft' && auth()->user()->role === 'creator')
                                    <form method="POST" action="{{ route('documents.requestDeletion', $document) }}" onsubmit="return confirm('Supprimer definitivement ce document ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm btn-danger">Supprimer</button>
                                    </form>
                                @endif

                                <a href="{{ route('documents.download', $document) }}" class="btn btn-ghost btn-sm">Source</a>
                                @if($document->status === 'finalized')
                                    <a href="{{ route('documents.export.pdf', $document) }}" class="btn btn-ghost btn-sm">PDF final</a>
                                @endif
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
@endsection
