@extends('layouts.app')

@section('title', 'Mes documents')

@section('content')
<div class="cards-row" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 1rem;">
    <a href="{{ route('documents.creator.index', ['status' => 'draft']) }}" class="stat-card {{ $status === 'draft' ? 'active' : '' }}">
        <div class="stat-label">Brouillons</div>
        <div class="stat-value">{{ (int) ($stats->drafts ?? 0) }}</div>
        <div class="stat-meta">Documents encore modifiables</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'pending_codification']) }}" class="stat-card {{ $status === 'pending_codification' ? 'active' : '' }}">
        <div class="stat-label">Codification</div>
        <div class="stat-value" style="color:#f59e0b;">{{ (int) ($stats->pending_codification ?? 0) }}</div>
        <div class="stat-meta">En attente chez l admin</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'in_validation']) }}" class="stat-card {{ $status === 'in_validation' ? 'active' : '' }}">
        <div class="stat-label">Validation</div>
        <div class="stat-value" style="color:#38bdf8;">{{ (int) ($stats->in_validation ?? 0) }}</div>
        <div class="stat-meta">Documents en circuit</div>
    </a>
</div>
<div class="cards-row" style="grid-template-columns: repeat(3, 1fr);">
    <a href="{{ route('documents.creator.index', ['status' => 'ready_for_pdf']) }}" class="stat-card {{ $status === 'ready_for_pdf' ? 'active' : '' }}">
        <div class="stat-label">Pret pour PDF</div>
        <div class="stat-value" style="color:#10b981;">{{ (int) ($stats->ready_for_pdf ?? 0) }}</div>
        <div class="stat-meta">Documents a convertir en PDF</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'rejected']) }}" class="stat-card {{ $status === 'rejected' ? 'active' : '' }}">
        <div class="stat-label">Rejetes</div>
        <div class="stat-value" style="color:#f87171;">{{ (int) ($stats->rejected ?? 0) }}</div>
        <div class="stat-meta">A corriger et renvoyer</div>
    </a>
    <a href="{{ route('documents.creator.index', ['status' => 'finalized']) }}" class="stat-card {{ $status === 'finalized' ? 'active' : '' }}">
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
        <a href="{{ route('documents.create') }}" class="btn btn-primary">Nouveau document</a>
    </div>

@if($documents->isEmpty())
        <div style="text-align:center;padding:3rem;color:var(--muted);">
            <i class="fas fa-file-alt fa-3x mb-3" style="color:rgba(255,255,255,0.15);display:block;"></i>
            <div style="font-size:.95rem;margin-bottom:.5rem;">
                @if($status === 'draft')
                    Aucun brouillon pour le moment.
                @elseif($status === 'pending_codification')
                    Aucun document en attente de codification.
                @elseif($status === 'in_validation')
                    Aucun document en cours de validation.
                @elseif($status === 'ready_for_pdf')
                    Aucun document pret pour PDF.
                @elseif($status === 'rejected')
                    Aucun document rejecte.
                @elseif($status === 'finalized')
                    Aucun document finalise.
                @else
                    Aucun document pour le moment.
                @endif
            </div>
            <a href="{{ route('documents.create') }}" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:.5rem;">
                <i class="fas fa-plus"></i> Creer mon premier document
            </a>
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
                                    'rejected' => ['badge-danger', 'Rejete'],
                                    'finalized' => ['badge-success', 'Finalise'],
                                ];
                                $status = $statusConfig[$document->status] ?? ['badge-muted', $document->status];
                            @endphp
                            <span class="badge {{ $status[0] }}">{{ $status[1] }}</span>
                        </td>
                        <td>
                            <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
                                @if(in_array($document->status, ['draft', 'rejected']))
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-ghost btn-sm">Modifier</a>
                                @endif

                                @if($document->status === 'draft' && empty($document->code))
                                    <form method="POST" action="{{ route('workflow.creator.send', $document) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Envoyer a l admin</button>
                                    </form>
                                @endif

                                @if($document->status === 'draft' && ! empty($document->code))
                                    <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Envoyer pour validation</button>
                                    </form>
                                @endif

                                @if($document->status === 'rejected' && ! empty($document->code))
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-ghost btn-sm">Modifier</a>
                                    <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Renvoyer pour validation</button>
                                    </form>
                                @endif

                                @if($document->status === 'ready_for_pdf')
                                    <form method="POST" action="{{ route('workflow.creator.sign_and_send', $document) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">Signer et envoyer</button>
                                    </form>
                                @endif

                                @if($document->status === 'draft')
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
