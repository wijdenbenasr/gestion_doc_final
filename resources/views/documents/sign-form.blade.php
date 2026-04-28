@extends('layouts.app')

@section('title', 'Signer le document')

@section('content')
<div class="card" style="max-width: 600px; margin: 2rem auto;">
    <div class="card-header">
        <div class="card-title">Signer le document</div>
        <div class="card-sub">Téléchargez le PDF sign pour envoi au validateur</div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div style="padding: 1.5rem;">
        <div style="margin-bottom: 1.5rem;">
            <h6 style="color: var(--muted); margin-bottom: 0.5rem;">Document</h6>
            <div style="font-weight: 500; font-size: 1.1rem;">{{ $document->name }}</div>
            @if($document->code)
                <div style="font-family: monospace; color: var(--accent); font-size: 0.85rem; margin-top: 0.25rem;">{{ $document->code }}</div>
            @endif
        </div>

        <div style="margin-bottom: 1.5rem;">
            <h6 style="color: var(--muted); margin-bottom: 0.5rem;">Statut actuel</h6>
            <span class="badge badge-success">PDF converti</span>
        </div>

        <form method="POST" action="{{ route('documents.sign.upload', $document->id) }}" enctype="multipart/form-data">
            @csrf

            <div style="margin-bottom: 1.5rem;">
                <label for="signed_pdf" style="display: block; margin-bottom: 0.5rem; font-weight: 500;">
                    <i class="fas fa-file-pdf me-2"></i>PDF sign
                </label>
                <input type="file" name="signed_pdf" id="signed_pdf" accept=".pdf" required
                       style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px; background: var(--card); color: var(--text);">
                <div style="font-size: 0.75rem; color: var(--muted); margin-top: 0.5rem;">
                    <i class="fas fa-info-circle me-1"></i>Format accept : PDF uniquement (max 10 Mo)
                </div>
                @error('signed_pdf')
                    <div style="color: var(--danger); font-size: 0.85rem; margin-top: 0.5rem;">{{ $message }}</div>
                @enderror
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-upload me-2"></i>Envoyer au validateur
                </button>
                <a href="{{ route('documents.my') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
        </form>
    </div>
</div>
@endsection




