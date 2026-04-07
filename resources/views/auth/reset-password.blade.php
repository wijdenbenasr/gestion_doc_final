@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
<div class="card" style="max-width:480px;margin:2rem auto;">
    <div class="card-header">
        <div>
            <div class="card-title">Nouveau mot de passe</div>
            <div class="card-sub">Saisissez votre nouveau mot de passe ci-dessous.</div>
        </div>
    </div>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-grid" style="grid-template-columns:1fr;">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
                @error('email')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required>
                @error('password')<div class="field-error">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Réinitialiser</button>
        </div>
    </form>
</div>
@endsection
