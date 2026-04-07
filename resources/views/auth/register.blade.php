@extends('layouts.app')

@section('title', 'Inscription')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Créer un compte</div>
                {{-- <div class="card-sub">Rôle unique par utilisateur, validation manuelle par un administrateur</div> --}}
            </div>
        </div>

        <form method="POST" action="{{ route('register.submit') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="name">Nom</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="prenom">Prénom</label>
                    <input id="prenom" type="text" name="prenom" value="{{ old('prenom') }}" required>
                </div>
                <div class="field">
                    <label for="cin">CIN</label>
                    <input id="cin" type="text" name="cin" value="{{ old('cin') }}" required>
                </div>
                <div class="field">
                    <label for="matricule">Matricule</label>
                    <input id="matricule" type="text" name="matricule" value="{{ old('matricule') }}" required>
                </div>
                <div class="field">
                    <label for="email">Email professionnel</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirmation mot de passe</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Créer le compte</button>
            </div>
        </form>
    </div>
@endsection

