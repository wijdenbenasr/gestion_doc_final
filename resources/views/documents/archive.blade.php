@extends('layouts.app')

@section('title', 'Archive des documents')

@section('content')
<style>
    .archive-container {
        min-height: calc(100vh - 80px);
        padding: 2rem;
        max-width: 1280px;
        margin: 0 auto;
    }
    .archive-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .archive-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: .5rem;
        background: linear-gradient(135deg, #e5e7eb 0%, #9ca3af 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .archive-sub {
        color: var(--muted);
        font-size: .9rem;
    }
    .search-section {
        background: rgba(15,23,42,0.5);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .search-bar-wrapper {
        display: flex;
        gap: .75rem;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
    }
    .search-input {
        flex: 1;
        min-width: 280px;
        max-width: 500px;
        padding: .75rem 1rem;
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: .65rem;
        color: var(--text);
        font-size: .95rem;
        transition: border-color .2s, box-shadow .2s;
    }
    .search-input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-soft);
    }
    .search-input::placeholder {
        color: var(--muted);
    }
    .btn-search {
        padding: .75rem 1.25rem;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: .65rem;
        font-size: .9rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .2s, transform .15s;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .btn-search:hover {
        background: #0284c7;
    }
    .btn-search:active {
        transform: scale(.98);
    }
    .btn-filter-toggle {
        padding: .75rem 1rem;
        background: transparent;
        color: var(--text);
        border: 1px solid var(--border);
        border-radius: .65rem;
        font-size: .9rem;
        cursor: pointer;
        transition: border-color .2s, background .2s;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .btn-filter-toggle:hover {
        border-color: var(--accent);
        background: var(--accent-soft);
    }
    .btn-filter-toggle.active {
        border-color: var(--accent);
        background: var(--accent-soft);
    }
    .filters-panel {
        display: none;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border);
    }
    .filters-panel.active {
        display: block;
    }
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }
    .filter-field {
        display: flex;
        flex-direction: column;
        gap: .4rem;
    }
    .filter-label {
        font-size: .75rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .filter-select,
    .filter-input {
        padding: .6rem .85rem;
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: .5rem;
        color: var(--text);
        font-size: .85rem;
        transition: border-color .2s;
    }
    .filter-select:focus,
    .filter-input:focus {
        outline: none;
        border-color: var(--accent);
    }
    .filter-actions {
        display: flex;
        gap: .75rem;
        margin-top: 1rem;
        justify-content: flex-end;
    }
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
        padding: 0 .5rem;
    }
    .results-count {
        font-size: .95rem;
        color: var(--muted);
    }
    .results-count strong {
        color: var(--accent);
        font-weight: 600;
    }
    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.25rem;
    }
    .doc-card {
        background: rgba(15,23,42,0.6);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.25rem;
        transition: border-color .2s, transform .2s, box-shadow .2s;
    }
    .doc-card:hover {
        border-color: rgba(56,189,248,0.4);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }
    .doc-card-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .doc-icon {
        width: 48px;
        height: 48px;
        border-radius: .75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .doc-icon-pdf {
        background: rgba(239,68,68,0.15);
        color: #ef4444;
    }
    .doc-icon-word {
        background: rgba(59,130,246,0.15);
        color: #3b82f6;
    }
    .doc-icon-excel {
        background: rgba(34,197,94,0.15);
        color: #22c55e;
    }
    .doc-icon-default {
        background: rgba(156,163,175,0.15);
        color: #9ca3af;
    }
    .doc-info {
        flex: 1;
        min-width: 0;
    }
    .doc-name {
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: .35rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .doc-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }
    .doc-badge {
        padding: .2rem .55rem;
        background: rgba(56,189,248,0.12);
        border: 1px solid rgba(56,189,248,0.25);
        border-radius: 999px;
        font-size: .7rem;
        color: var(--accent);
    }
    .doc-details {
        padding: .85rem 0;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        display: flex;
        flex-wrap: wrap;
        gap: .75rem 1.5rem;
        font-size: .82rem;
    }
    .doc-detail {
        display: flex;
        flex-direction: column;
        gap: .15rem;
    }
    .doc-detail-label {
        font-size: .68rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .doc-detail-value {
        color: var(--text);
        font-weight: 500;
    }
    .doc-card-actions {
        display: flex;
        gap: .75rem;
        margin-top: 1rem;
    }
    .btn-doc {
        flex: 1;
        padding: .6rem 1rem;
        border-radius: .5rem;
        font-size: .85rem;
        font-weight: 500;
        text-align: center;
        transition: background .2s, border-color .2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
    }
    .btn-doc-download {
        background: var(--accent);
        color: #fff;
        border: none;
    }
    .btn-doc-download:hover {
        background: #0284c7;
    }
    .btn-doc-view {
        background: transparent;
        color: var(--text);
        border: 1px solid var(--border);
    }
    .btn-doc-view:hover {
        border-color: var(--accent);
        background: var(--accent-soft);
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }
    .empty-icon {
        font-size: 4rem;
        color: var(--muted);
        margin-bottom: 1.5rem;
        opacity: .5;
    }
    .empty-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: .5rem;
        color: var(--text);
    }
    .empty-message {
        color: var(--muted);
        font-size: .95rem;
    }
    .pagination-wrapper {
        margin-top: 2rem;
        display: flex;
        justify-content: center;
    }
    @media (max-width: 768px) {
        .archive-container {
            padding: 1rem;
        }
        .results-grid {
            grid-template-columns: 1fr;
        }
        .doc-card-actions {
            flex-direction: column;
        }
    }
</style>

<div class="archive-container">
    <div class="archive-header">
        <h1 class="archive-title">Archive des documents finalisés</h1>
        <p class="archive-sub">Consultez et téléchargez les documents finalisés</p>
    </div>

    <div class="search-section">
        <form method="GET" action="{{ route('documents.archive') }}">
            <div class="search-bar-wrapper">
                <input
                    type="text"
                    name="search"
                    class="search-input"
                    placeholder="Rechercher un document..."
                    value="{{ $filters['search'] ?? '' }}"
                >
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                    Rechercher
                </button>
                <button type="button" class="btn-filter-toggle" id="filterToggle">
                    <i class="fas fa-sliders-h"></i>
                    Filtres avancés
                </button>
            </div>

            <div class="filters-panel" id="filtersPanel">
                <div class="filters-grid">
                    <div class="filter-field">
                        <label class="filter-label">Type</label>
                        <select name="type" class="filter-select">
                            <option value="">Tous les types</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label">AIO</label>
                        <select name="aio" class="filter-select">
                            <option value="">Tous les AIO</option>
                            @foreach($aios as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['aio'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label">Ligne</label>
                        <input type="text" name="ligne" class="filter-input" placeholder="Toutes les lignes" value="{{ $filters['ligne'] ?? '' }}">
                    </div>
                    <div class="filter-field">
                        <label class="filter-label">Phase</label>
                        <select name="phase" class="filter-select">
                            <option value="">Toutes les phases</option>
                            <option value="serie" {{ ($filters['phase'] ?? '') === 'serie' ? 'selected' : '' }}>Série</option>
                            <option value="projet" {{ ($filters['phase'] ?? '') === 'projet' ? 'selected' : '' }}>Projet</option>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="filter-label">Date de</label>
                        <input type="date" name="date_from" class="filter-input" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="filter-field">
                        <label class="filter-label">Date </label>
                        <input type="date" name="date_to" class="filter-input" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-filter"></i>
                        Appliquer
                    </button>
                    <a href="{{ route('documents.archive') }}" class="btn-filter-toggle">
                        <i class="fas fa-times"></i>
                        Rinitialiser
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if($documents->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-folder-open"></i>
            </div>
            <h3 class="empty-title">Aucun document trouv</h3>
            <p class="empty-message">Aucun document finalis ne correspond  vos critres de recherche.</p>
        </div>
    @else
        <div class="results-header">
            <div class="results-count">
                <strong>{{ $documents->total() }}</strong> document(s) trouv(s)
            </div>
        </div>

        <div class="results-grid">
            @foreach($documents as $document)
                @php
                    $ext = strtolower(pathinfo($document->file_original_name, PATHINFO_EXTENSION));
                    $iconClass = match($ext) {
                        'pdf' => 'doc-icon-pdf fas fa-file-pdf',
                        'doc', 'docx' => 'doc-icon-word fas fa-file-word',
                        'xls', 'xlsx' => 'doc-icon-excel fas fa-file-excel',
                        default => 'doc-icon-default fas fa-file'
                    };
                @endphp
                <div class="doc-card">
                    <div class="doc-card-header">
                        <div class="doc-icon {{ $iconClass }}">
                            <i class="fas fa-file"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-name" title="{{ $document->name }}">{{ $document->name }}</div>
                            <div class="doc-meta">
                                <span class="doc-badge">{{ $document->code }}</span>
                                <span class="doc-badge">{{ \App\Models\Document::AIOS[$document->aio] ?? $document->aio }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="doc-details">
                        <div class="doc-detail">
                            <span class="doc-detail-label">Phase</span>
                            <span class="doc-detail-value">{{ $document->phase_libelle ?? '-' }}</span>
                        </div>
                        <div class="doc-detail">
                            <span class="doc-detail-label">Ligne</span>
                            <span class="doc-detail-value">{{ $document->ligne ?? '-' }}</span>
                        </div>
                        <div class="doc-detail">
                            <span class="doc-detail-label">Finalis le</span>
                            <span class="doc-detail-value">{{ $document->updated_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <div class="doc-card-actions">
                        <a href="{{ route('documents.download', $document) }}" class="btn-doc btn-doc-download">
                            <i class="fas fa-download"></i>
                            Télécharger
                        </a>
                        @if(auth()->user()->role === 'admin')
                            <a href="{{ route('documents.view', $document) }}" target="_blank" class="btn-doc btn-doc-view">
                                <i class="fas fa-eye"></i>
                                Voir
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pagination-wrapper">
            {{ $documents->links() }}
        </div>
    @endif
</div>

<script>
document.getElementById('filterToggle').addEventListener('click', function() {
    const panel = document.getElementById('filtersPanel');
    const btn = this;
    panel.classList.toggle('active');
    btn.classList.toggle('active');
});

const hasActiveFilters = {{ isset($filters['type']) && $filters['type'] ? 'true' : 'false' }}
    || {{ isset($filters['aio']) && $filters['aio'] ? 'true' : 'false' }}
    || {{ isset($filters['ligne']) && $filters['ligne'] ? 'true' : 'false' }}
    || {{ isset($filters['phase']) && $filters['phase'] ? 'true' : 'false' }}
    || {{ isset($filters['date_from']) && $filters['date_from'] ? 'true' : 'false' }}
    || {{ isset($filters['date_to']) && $filters['date_to'] ? 'true' : 'false' }};

if (hasActiveFilters) {
    document.getElementById('filtersPanel').classList.add('active');
    document.getElementById('filterToggle').classList.add('active');
}
</script>
@endsection




