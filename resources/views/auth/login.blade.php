@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Connexion</div>
                <div class="card-sub">Accès sécurisé à la plateforme documentaire qualité</div>
            </div>
        </div>

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Mot de passe</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--muted);">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Se souvenir de moi</span>
                    </label>
                </div>
                <div class="field">
                    <label>&nbsp;</label>
                    <a href="{{ route('password.request') }}" style="font-size:.8rem;color:var(--accent);">
                        Mot de passe oublié ?
                    </a>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Se connecter</button>
            </div>
        </form>
    </div>
@endsection

