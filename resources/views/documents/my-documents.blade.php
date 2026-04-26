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
        <div style="overflow-x:auto;position:relative;">
            <table style="min-width:900px;position:relative;">
                <thead>
                <tr>
                    <th style="width:180px;">Nom</th>
                    <th style="width:100px;">Code</th>
                    <th style="width:140px;">Type</th>
                    <th style="width:80px;">AIO</th>
                    <th style="width:100px;">Ligne</th>
                    <th style="width:120px;">Phase</th>
                    <th style="width:60px;">Rev.</th>
                    <th style="width:100px;">Deadline</th>
                    <th style="width:100px;">Statut</th>
                    <th style="width:120px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach($documents as $document)
                    <tr>
                        <td style="font-weight:500;max-width:180px;" title="{{ $document->name }}">
                            <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $document->name }}</div>
                        </td>
                        <td>
                            @if($document->code)
                                <span style="font-family:monospace;font-size:.73rem;color:var(--accent);">{{ $document->code }}</span>
                            @else
                                <span class="badge bg-secondary">Non codifie</span>
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
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleMenu(this)">Actions ▾</button>
                            <ul class="action-menu" style="display:none;position:fixed;background:#1a2035;border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:8px 0;z-index:9999;min-width:160px;list-style:none;margin:0;">
                                @if(in_array($document->status, ['draft', 'rejected']) && auth()->user()->role === 'creator')
                                    <li><a class="dropdown-item" href="{{ route('documents.edit', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-edit"></i> Modifier</a></li>
                                @endif

                                @if($document->status === 'draft' && empty($document->code) && auth()->user()->role === 'creator')
                                    <li>
                                        <form method="POST" action="{{ route('workflow.creator.send', $document) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#e5e7eb;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-paper-plane"></i> Envoyer a l admin</button>
                                        </form>
                                    </li>
                                @endif

                                @if($document->status === 'draft' && ! empty($document->code) && auth()->user()->role === 'creator')
                                    <li>
                                        <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#e5e7eb;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-paper-plane"></i> Envoyer pour validation</button>
                                        </form>
                                    </li>
                                @endif

                                @if($document->status === 'rejected' && ! empty($document->code) && auth()->user()->role === 'creator')
                                    <li><a class="dropdown-item" href="{{ route('documents.edit', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-edit"></i> Modifier</a></li>
                                    <li>
                                        <form method="POST" action="{{ route('workflow.creator.send_to_validator', $document) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#e5e7eb;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-paper-plane"></i> Renvoyer pour validation</button>
                                        </form>
                                    </li>
                                @endif

                                @if($document->status === 'ready_for_pdf' && auth()->user()->role === 'creator')
                                    <li>
                                        <form method="POST" action="{{ route('workflow.creator.sign_and_send', $document) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#e5e7eb;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-signature"></i> Signer et envoyer</button>
                                        </form>
                                    </li>
                                @endif

                                @if($document->status === 'draft' && auth()->user()->role === 'creator')
                                    <li style="border-top:1px solid rgba(255,255,255,0.1);margin:4px 0;"></li>
                                    <li>
                                        <form method="POST" action="{{ route('documents.requestDeletion', $document) }}" onsubmit="return confirm('Supprimer definitivement ce document ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item" style="display:block;width:100%;padding:6px 12px;border:none;background:none;color:#f87171;text-align:left;font-size:.75rem;cursor:pointer;"><i class="fas fa-trash"></i> Supprimer</button>
                                        </form>
                                    </li>
                                @endif

                                <li><a class="dropdown-item" href="{{ route('documents.download', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-code"></i> Source</a></li>
                                @if($document->status === 'finalized')
                                    <li><a class="dropdown-item" href="{{ route('documents.export.pdf', $document) }}" style="display:block;padding:6px 12px;color:#e5e7eb;text-decoration:none;font-size:.75rem;"><i class="fas fa-file-pdf"></i> PDF final</a></li>
                                @endif
                            </ul>
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
