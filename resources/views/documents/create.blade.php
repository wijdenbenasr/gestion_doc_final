@extends('layouts.app')

@section('title', isset($document) ? 'Modifier le document' : 'Nouveau document')

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">
                {{ isset($document) ? 'Modifier : ' . $document->name : 'Créer un nouveau document' }}
            </div>
            <div class="card-sub">
                Remplissez tous les champs obligatoires (*). Le fichier sera chiffré et un hash SHA-256 calculé.
            </div>
        </div>
        <a href="{{ $backRoute ?? route('documents.creator.index') }}" class="btn btn-ghost">← Retour</a>
    </div>

    <form method="POST"
          action="{{ isset($document) ? route('documents.update', $document) : route('documents.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if(isset($document)) @method('PUT') @endif

        {{-- ── Section 1 : Identification ──────────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-title">Identification du document</div>
            <div class="form-grid">

                {{-- Nom --}}
                <div class="field field-full">
                    <label for="name">Nom du document <span class="required">*</span></label>
                    <input id="name" type="text" name="name"
                           value="{{ old('name', $document->name ?? '') }}"
                           placeholder="Ex : Fiche de contrôle poste AIO1 - Ligne 3"
                           required>
                    @error('name')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                {{-- Type de document --}}
                <div class="field">
                    <label for="type">Type de document <span class="required">*</span></label>
                    <select id="type" name="type" required>
                        <option value="">-- Sélectionner un type --</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('type', $document->type ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                {{-- AIO --}}
                <div class="field">
                    <label for="aio">AIO <span class="required">*</span></label>
                    <select id="aio" name="aio" required>
                        <option value="">-- Sélectionner un AIO --</option>
                        @foreach($aios as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('aio', $document->aio ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('aio')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                {{-- Ligne de production --}}
                <div class="field">
                    <label for="ligne">
                        Ligne de production
                        <span class="required">*</span>
                        @if(!isset($document) && !$canEditLigne)
                            <span class="field-badge field-badge-optional"><i class="fas fa-lock"></i> Modifiable par l'admin apres codification</span>
                        @elseif(isset($document) && !$canEditLigne)
                            <span class="field-badge field-badge-optional"><i class="fas fa-lock"></i> Modifiable par l'admin uniquement</span>
                        @endif
                    </label>
                    <input id="ligne" type="text" name="ligne"
                           value="{{ old('ligne', $document->ligne ?? '') }}"
                           placeholder="Ex : Ligne A, Ligne 3, L12..."
                           required
                           @if(!$canEditLigne) readonly @endif
                           style="{{ !$canEditLigne ? 'background:rgba(148,163,184,0.08);color:var(--muted);' : '' }}">
                    @error('ligne')<div class="field-error">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        {{-- ── Section 2 : Phase / Série ───────────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-title">Phase / Série</div>
            <div class="form-grid">

                {{-- Phase --}}
                <div class="field">
                    <label for="phase">Phase <span class="required">*</span></label>
                    <select id="phase" name="phase" required onchange="handlePhaseChange()">
                        <option value="">-- Sélectionner --</option>
                        <option value="serie"  @selected(old('phase', $document->phase ?? '') === 'serie')>Série</option>
                        <option value="projet" @selected(old('phase', $document->phase ?? '') === 'projet')>Projet</option>
                    </select>
                    @error('phase')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                {{-- Nom de la phase (OBLIGATOIRE si Projet) --}}
                <div class="field" id="field-nom-phase" style="display:none;">
                    <label for="nom_phase">
                        Nom de la phase <span class="required">*</span>
                        <span class="field-badge">Projet</span>
                    </label>
                    <input id="nom_phase" type="text" name="nom_phase"
                           value="{{ old('nom_phase', $document->nom_phase ?? '') }}"
                           placeholder="Ex : Phase Développement, Phase Pilote, P1...">
                    <div class="field-hint">Obligatoire quand le type est « Projet ».</div>
                    @error('nom_phase')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                {{-- Numéro / Nom de série (OPTIONNEL si Série) --}}
                <div class="field" id="field-nom-serie" style="display:none;">
                    <label for="nom_serie">
                        Numéro / Nom de série
                        <span class="field-badge field-badge-optional">Optionnel</span>
                    </label>
                    <input id="nom_serie" type="text" name="nom_serie"
                           value="{{ old('nom_serie', $document->nom_serie ?? '') }}"
                           placeholder="Ex : S2024-01, Série B, Rev3...">
                    @error('nom_serie')<div class="field-error">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        {{-- ── Section 3 : Fichier & Deadline ──────────────────────────────── --}}
        <div class="form-section">
            <div class="form-section-title">Fichier & Planning</div>
            <div class="form-grid">

{{-- Fichier --}}
                <div class="field">
                    <label for="file">
                        Fichier
                        @if(!isset($document))
                            <span class="required">*</span>
                        @else
                            <span class="field-badge field-badge-optional">Optionnel - laisser vide pour garder l'actuel</span>
                        @endif
                    </label>
                    <label for="fichier" class="btn-upload">
                        <i class="fas fa-upload"></i> Choisir un fichier
                    </label>
                    <input id="fichier" type="file" name="file" hidden
                           accept=".docx,.xlsx,.pdf"
                           {{ !isset($document) ? 'required' : '' }}>
                    <div id="file-name" class="field-hint" style="margin-top:.5rem;"></div>
                    <div class="field-hint">
                        Formats acceptes : <strong>.docx</strong>, <strong>.xlsx</strong>, <strong>.pdf</strong> - Taille max : 20 Mo
                    </div>
                    @if(isset($document))
                        <div class="field-hint" style="color:var(--accent);">
                            Fichier actuel : {{ $document->file_original_name }}
                        </div>
                    @endif
                    @error('file')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                {{-- Deadline --}}
                <div class="field">
                    <label for="deadline">Deadline de traitement</label>
                    <input id="deadline" type="datetime-local" name="deadline"
                           value="{{ old('deadline', isset($document) && $document->deadline ? $document->deadline->format('Y-m-d\TH:i') : '') }}">
                    <div class="field-hint">
                        Des notifications seront envoyées 24h avant et après la deadline.
                    </div>
                    @error('deadline')<div class="field-error">{{ $message }}</div>@enderror
                </div>

            </div>
        </div>

        <div class="form-actions">
            <a href="{{ $backRoute ?? route('documents.creator.index') }}" class="btn btn-ghost">Annuler</a>
            <button type="submit" class="btn btn-primary">
                {{ isset($document) ? '💾 Enregistrer les modifications' : '+ Créer le document' }}
            </button>
        </div>
    </form>
</div>

<style>
.btn-upload {
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    padding:.625rem 1rem;
    background:#374151;
    color:#fff;
    border-radius:.375rem;
    cursor:pointer;
    font-size:.875rem;
    font-weight:500;
    transition:background .15s;
    border:1px solid rgba(55,65,81,.5);
}
.btn-upload:hover{background:#4b5563}
.form-section {
    border: 1px solid var(--border);
    border-radius: .85rem;
    padding: 1.1rem 1.2rem;
    margin-bottom: 1rem;
    background: rgba(15,23,42,0.5);
}
.form-section-title {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--accent);
    font-weight: 600;
    margin-bottom: .85rem;
    padding-bottom: .4rem;
    border-bottom: 1px solid rgba(14,165,233,0.2);
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: .85rem 1.25rem;
}
.field-full { grid-column: 1 / -1; }
.required { color: var(--danger); margin-left: .15rem; }
.field-badge {
    display: inline-block;
    padding: .05rem .4rem;
    border-radius: .3rem;
    font-size: .62rem;
    background: rgba(14,165,233,0.15);
    color: var(--accent);
    margin-left: .3rem;
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
}
.field-badge-optional {
    background: rgba(148,163,184,0.12);
    color: var(--muted);
}
.btn-primary {
    background: linear-gradient(135deg, rgba(14,165,233,0.3), rgba(15,23,42,0.95));
    border-color: rgba(14,165,233,0.6);
    font-weight: 600;
}
select option { background: #020617; color: #e5e7eb; }
@media (max-width: 700px) {
    .form-grid { grid-template-columns: 1fr; }
    .field-full { grid-column: 1; }
}
</style>

<script>
document.getElementById('fichier').addEventListener('change',function(e){
    var name=e.target.files[0]?.name||'';
    document.getElementById('file-name').textContent=name;
});
function handlePhaseChange() {
    var phase     = document.getElementById('phase').value;
    var phaseDiv  = document.getElementById('field-nom-phase');
    var serieDiv  = document.getElementById('field-nom-serie');
    var nomPhase  = document.getElementById('nom_phase');

    phaseDiv.style.display = (phase === 'projet') ? 'flex' : 'none';
    serieDiv.style.display = (phase === 'serie')  ? 'flex' : 'none';

    nomPhase.required = (phase === 'projet');
}

// Initialisation au chargement (important pour la vue Modifier)
document.addEventListener('DOMContentLoaded', function () {
    handlePhaseChange();
});
</script>
@endsection
