@extends('layouts.app')

@section('title', 'Changer le mot de passe')

@section('content')
<div class="card" style="max-width:560px;">
    <div class="card-header">
        <div>
            <div class="card-title">Changer le mot de passe</div>
            <div class="card-sub">Mettez a jour votre mot de passe sans attendre un email de reinitialisation.</div>
        </div>
        <a href="{{ route('account.profile.show') }}" class="btn btn-ghost btn-sm">Mon profil</a>
    </div>

    <form method="POST" action="{{ route('account.password.update') }}">
        @csrf
        @method('PUT')

        <div class="form-grid" style="grid-template-columns:1fr;">
            <div class="field">
                <label for="current_password">Mot de passe actuel</label>
                <input id="current_password" type="password" name="current_password" required>
                @error('current_password')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required>
                @error('password')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirmation</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Mettre a jour</button>
        </div>
    </form>
</div>
@endsection
