@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
<style>
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
            <form id="profilePhotoForm" method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" style="margin:0;">
                @csrf
                <input id="profilePhotoInput" type="file" name="profile_photo" accept="image/jpeg,image/png,image/jpg,image/gif">
            </form>

            <span id="profilePhotoAvatar" role="button" tabindex="0" aria-label="Changer la photo de profil" title="Cliquer pour changer la photo" style="display:inline-block;cursor:pointer;user-select:none;">
                @if($user->profile_photo)
                    <img id="profilePhotoImg" src="{{ \Illuminate\Support\Facades\Storage::url($user->profile_photo) }}"
                         style="width:110px;height:110px;border-radius:50%;object-fit:cover;object-position:center;border:3px solid #3b82f6;"
                         alt="Photo de profil de {{ $user->full_name }}">
                    <span id="profilePhotoInitials" style="display:none;"></span>
                @else
                    <div id="profilePhotoInitials" style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:bold;color:white;">
                        {{ strtoupper(substr($user->prenom,0,1)) }}{{ strtoupper(substr($user->name,0,1)) }}
                    </div>
                    <img id="profilePhotoImg" src="" style="display:none;" alt="">
                @endif
            </span>

            <div style="display:grid;gap:.5rem;width:100%;">
                <button type="button" id="profilePhotoPickBtn" class="btn btn-primary" style="width:100%;">
                    {{ $user->has_profile_photo ? 'Remplacer la photo' : 'Ajouter une photo' }}
                </button>

                @if($user->has_profile_photo)
                    <button type="button"
                            onclick="openGlobalDeleteModal('{{ route('profile.photo.delete') }}', '{{ $user->full_name }}', 'Supprimer la photo de profil')"
                            class="btn btn-ghost" style="width:100%;border-color:rgba(248,113,113,0.45);color:#fecaca;">
                        Supprimer la photo
                    </button>
                @endif
            </div>

            <div style="text-align:center;">
                <div style="font-size:1rem;font-weight:700;">{{ $user->full_name }}</div>
                <div class="card-sub">{{ $user->email }}</div>
                <div class="card-sub" style="margin-top:.35rem;">{{ $user->role_label }}</div>
            </div>

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

        </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const form = document.getElementById('profilePhotoForm');
    const input = document.getElementById('profilePhotoInput');
    const avatar = document.getElementById('profilePhotoAvatar');
    const pickBtn = document.getElementById('profilePhotoPickBtn');
    const img = document.getElementById('profilePhotoImg');
    const initials = document.getElementById('profilePhotoInitials');

    if (!form || !input || !avatar) {
        return;
    }

    const openPicker = () => input.click();

    avatar.addEventListener('click', openPicker);
    avatar.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openPicker();
        }
    });

    if (pickBtn) {
        pickBtn.addEventListener('click', openPicker);
    }

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) {
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        if (img) {
            img.src = objectUrl;
            img.style.display = '';
            img.onload = () => URL.revokeObjectURL(objectUrl);
        }
        if (initials && initials.tagName === 'DIV') {
            initials.style.display = 'none';
        }

        requestAnimationFrame(() => {
            setTimeout(() => form.submit(), 150);
        });
    });
})();
</script>
@endsection




