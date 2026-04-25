@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<style>
.file-upload-btn {
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
}
.file-upload-btn:hover { background:#4b5563; }
input[type="file"] { display:none; }
</style>
<div style="display:grid;grid-template-columns:minmax(0,340px) minmax(0,1fr);gap:1rem;align-items:start;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Image de profil</div>
                <div class="card-sub">Ajoutez, remplacez ou supprimez votre photo depuis cet espace.</div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;align-items:center;gap:1rem;">
            <span class="profile-avatar" style="width:120px;height:120px;font-size:2rem;">
                @if($user->profile_image_url)
                    <img src="{{ $user->profile_image_url }}" alt="Photo de profil de {{ $user->full_name }}">
                @else
                    {{ $user->initials }}
                @endif
            </span>

            <div style="text-align:center;">
                <div style="font-size:1rem;font-weight:700;">{{ $user->full_name }}</div>
                <div class="card-sub">{{ $user->email }}</div>
                <div class="card-sub" style="margin-top:.35rem;">{{ $user->role_label }}</div>
            </div>

            <form method="POST" action="{{ route('account.profile.image.update') }}" enctype="multipart/form-data" style="width:100%;">
                @csrf

                <div class="field">
                    <label for="profile_image" class="file-upload-btn">
                        <i class="fa fa-folder-open"></i> Choisir une image
                    </label>
                    <input id="profile_image" type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                    <div class="card-sub">Formats acceptes : JPG, PNG, WEBP. Taille max : 2 Mo.</div>
                    @error('profile_image')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div class="form-actions" style="justify-content:stretch;flex-direction:column;">
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        {{ $user->profile_image_path ? 'Remplacer l image' : 'Enregistrer l image' }}
                    </button>
                </div>
            </form>

            @if($user->profile_image_path)
                <form method="POST" action="{{ route('account.profile.image.destroy') }}" style="width:100%;">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger" style="width:100%;">Supprimer l image</button>
                </form>
            @endif

            <a href="{{ route('account.password.edit') }}" class="btn btn-ghost" style="width:100%;">Changer le mot de passe</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Informations du compte</div>
                <div class="card-sub">Vos informations visibles dans le menu profil de la navbar.</div>
            </div>
        </div>

        <div class="form-grid">
            <div class="field">
                <label>Nom</label>
                <input type="text" value="{{ $user->name ?: '-' }}" readonly style="opacity:.6;cursor:not-allowed;">
            </div>

            <div class="field">
                <label>Prenom</label>
                <input type="text" value="{{ $user->prenom ?: '-' }}" readonly style="opacity:.6;cursor:not-allowed;">
            </div>

            <div class="field">
                <label>CIN</label>
                <input type="text" value="{{ $user->cin ?: '-' }}" readonly style="opacity:.6;cursor:not-allowed;">
            </div>

            <div class="field">
                <label>Matricule</label>
                <input type="text" value="{{ $user->matricule ?: '-' }}" readonly style="opacity:.6;cursor:not-allowed;">
            </div>

            <div class="field">
                <label>Email</label>
                <input type="text" value="{{ $user->email }}" readonly style="opacity:.6;cursor:not-allowed;">
            </div>

            <div class="field">
                <label>Role</label>
                <input type="text" value="{{ $user->role_label }}" readonly style="opacity:.6;cursor:not-allowed;">
            </div>
        </div>

        <div class="form-actions" style="margin-top:1rem;">
            <button type="button" class="btn btn-primary" style="width:100%;">Enregistrer les informations</button>
        </div>
    </div>
</div>
@endsection