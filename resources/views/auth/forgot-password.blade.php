@extends('layouts.app')

@section('title', 'Mot de passe oublié')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Réinitialiser le mot de passe</div>
                <div class="card-sub">Un lien sécurisé sera envoyé à votre email professionnel.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Envoyer le lien de réinitialisation</button>
            </div>
        </form>
    </div>
@endsection

