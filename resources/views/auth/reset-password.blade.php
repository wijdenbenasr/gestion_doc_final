@extends('layouts.app')

@section('title', 'Réinitialiser le mot de passe')

@section('styles')
    <style>
        header a.nav-link[href="{{ route('login') }}"],
        header a.btn[href="{{ route('register') }}"] {
            display: none !important;
        }
    </style>
@endsection

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
        @if($errors->any())
        <div class="form-error" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.4);color:#fca5a5;padding:.75rem 1rem;border-radius:.5rem;font-size:.85rem;margin-bottom:1rem;">
            @foreach($errors->all() as $error)
                {{ $error }}<br>
            @endforeach
        </div>
        @endif
        <div class="form-grid" style="grid-template-columns:1fr;">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
            </div>
            <div class="field">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" minlength="8" title="Le mot de passe doit contenir au moins 8 caractères" required>
                <small style="color:#64748b;font-size:0.78rem;">Minimum 8 caractères</small>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirmer le mot de passe</label>
                <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Réinitialiser</button>
        </div>
    </form>
</div>
@endsection


