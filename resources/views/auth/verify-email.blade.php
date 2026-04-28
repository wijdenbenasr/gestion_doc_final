@extends('layouts.app')

@section('title', 'Vérification email')

@section('content')
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Vérification email</div>
                <div class="card-sub">
                    Entrez le code de vérification reçu par email pour activer votre compte.
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('auth.verify.submit') }}">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required>
                </div>
                <div class="field">
                    <label for="code">Code à 6 chiffres</label>
                    <input id="code" type="text" name="code" maxlength="6" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Valider</button>
                <button type="submit" formaction="{{ route('auth.verify.resend') }}" class="btn btn-ghost">
                    Renvoyer le code
                </button>
            </div>
        </form>
    </div>
@endsection


