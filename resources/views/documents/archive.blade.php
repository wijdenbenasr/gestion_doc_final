@extends('layouts.app')

@section('title', 'Archive des documents')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Archive des documents finalisés</div>
            <div class="card-sub">Consultez et téléchargez les documents finalisés avec recherche et filtres.</div>
        </div>
    </div>

    <!-- Filtres -->
    <form method="GET" class="form-grid" style="padding: 1rem; border-bottom: 1px solid var(--border);">
        <div class="field">
            <label>Nom du document</label>
            <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Rechercher par nom...">
        </div>
        <div class="field">
            <label>Type</label>
            <select name="type">
                <option value="">Tous les types</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>AIO</label>
            <select name="aio">
                <option value="">Tous les AIO</option>
                @foreach($aios as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['aio'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label>Ligne</label>
            <input type="text" name="ligne" value="{{ $filters['ligne'] ?? '' }}" placeholder="Rechercher par ligne...">
        </div>
        <div class="field">
            <label>Phase</label>
            <select name="phase">
                <option value="">Toutes les phases</option>
                <option value="serie" {{ ($filters['phase'] ?? '') === 'serie' ? 'selected' : '' }}>Série</option>
                <option value="projet" {{ ($filters['phase'] ?? '') === 'projet' ? 'selected' : '' }}>Projet</option>
            </select>
        </div>
        <div class="field">
            <label>Date de création (de)</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="field">
            <label>Date de création (à)</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="field" style="grid-column: span 2; display: flex; gap: .5rem; align-items: end;">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('documents.archive') }}" class="btn btn-ghost">Réinitialiser</a>
        </div>
    </form>

    @if($documents->isEmpty())
        <div style="text-align:center;padding:2rem;color:var(--muted);">
            <div>Aucun document finalisé trouvé.</div>
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
                    <th>Créateur</th>
                    <th>Date de finalisation</th>
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
                            <span style="font-family:monospace;font-size:.73rem;color:var(--accent);">{{ $document->code }}</span>
                        </td>
                        <td style="font-size:.72rem;max-width:140px;" title="{{ $document->type_libelle }}">{{ \Illuminate\Support\Str::limit($document->type_libelle, 28) }}</td>
                        <td><span class="badge badge-info">{{ \App\Models\Document::AIOS[$document->aio] ?? $document->aio }}</span></td>
                        <td>{{ $document->ligne }}</td>
                        <td style="font-size:.72rem;">{{ $document->phase_libelle }}</td>
                        <td style="font-family:monospace;font-size:.75rem;">v{{ $document->revision }}</td>
                        <td>{{ $document->creator->name ?? '—' }} {{ $document->creator->prenom ?? '' }}</td>
                        <td style="font-size:.72rem;">
                            {{ $document->updated_at->format('d/m/Y') }}
                        </td>
                        <td>
                            <div style="display:flex;gap:.25rem;flex-wrap:wrap;">
                                <a href="{{ route('documents.download', $document) }}" class="btn btn-ghost btn-sm">Source</a>
                                <a href="{{ route('documents.export.pdf', $document) }}" class="btn btn-ghost btn-sm">PDF final</a>
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
