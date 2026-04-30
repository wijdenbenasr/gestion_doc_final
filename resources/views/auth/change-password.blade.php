@extends('layouts.app')

@section('title', 'Changer le mot de passe')

@section('content')
<style>
.password-toggle {
    position:absolute;
    right:.75rem;top:50%;transform:translateY(-50%);
    background:none;border:none;cursor:pointer;color:#6b7280;padding:.25rem;
}
.password-toggle:hover{color:#374151}
</style>
<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem;">
    <div class="card" style="max-width:560px;width:100%;">
        <div class="card-header">
            <div>
                <div class="card-title">Changer le mot de passe</div>
                <div class="card-sub">Mettez à jour votre mot de passe sans attendre un email de réinitialisation.</div>
            </div>
            <a href="{{ route('account.profile.show') }}" class="btn btn-ghost btn-sm">Mon profil</a>
        </div>

        @if(session('success'))
            <div style="text-align:center;padding:2rem;">
                <div style="font-size:3rem;margin-bottom:1rem;"></div>
                <h5 style="color:white;font-weight:bold;">{{ session('success') }}</h5>
                <p style="color:#94a3b8;">Votre mot de passe a été mis à jour avec succès.</p>
                <a href="{{ route('account.profile.show') }}"
                   style="display:inline-block;margin-top:1rem;padding:10px 24px;background:#3b82f6;color:white;border-radius:8px;text-decoration:none;">
                    Retour au profil
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('account.password.update') }}">
                @csrf
                @method('PUT')

                <div class="form-grid" style="grid-template-columns:1fr;">
                    <div class="field">
                        <label for="current_password">Mot de passe actuel</label>
                        <div style="position:relative;">
                            <input id="current_password" type="password" name="current_password" required style="padding-right:2.5rem;">
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')" tabindex="-1">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        @error('current_password')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password">Nouveau mot de passe</label>
                        <div style="position:relative;">
                            <input id="password" type="password" name="password" minlength="8" title="Le mot de passe doit contenir au moins 8 caractères" required style="padding-right:2.5rem;" oninput="checkPasswordStrength(this.value)">
                            <button type="button" class="password-toggle" onclick="togglePassword('password')" tabindex="-1">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                        <small style="color:#64748b;font-size:0.78rem;">Minimum 8 caractères</small>
                        <div id="password-strength" style="margin-top:.5rem;height:4px;background:#e5e7eb;border-radius:2px;overflow:hidden;display:flex;">
                            <div id="strength-bar" style="height:100%;width:0;transition:all .3s;"></div>
                        </div>
                        <div id="strength-text" style="font-size:.75rem;margin-top:.25rem;"></div>
                        @error('password')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Confirmation du nouveau mot de passe</label>
                        <div style="position:relative;">
                            <input id="password_confirmation" type="password" name="password_confirmation" minlength="8" required style="padding-right:2.5rem;">
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')" tabindex="-1">
                                <i class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="width:100%;">Mettre à jour</button>
                </div>
            </form>
        @endif
    </div>
</div>
<script>
function togglePassword(id){
    var input=document.getElementById(id);
    var btn=input.nextElementSibling.querySelector('i');
    if(input.type==='password'){
        input.type='text';
        btn.classList.replace('fa-eye','fa-eye-slash');
    }else{
        input.type='password';
        btn.classList.replace('fa-eye-slash','fa-eye');
    }
}
function checkPasswordStrength(pwd){
    var bar=document.getElementById('strength-bar');
    var text=document.getElementById('strength-text');
    var score=0;
    if(pwd.length>=8)score++;
    if(pwd.length>=12)score++;
    if(/[a-z]/.test(pwd)&&/[A-Z]/.test(pwd))score++;
    if(/\d/.test(pwd))score++;
    if(/[^a-zA-Z0-9]/.test(pwd))score++;
    var colors=['#ef4444','#f97316','#eab308','#22c55e','#15803d'];
    var texts=['Faible','Faible','Moyen','Fort','Fort'];
    bar.style.width=(score*20)+'%';
    bar.style.backgroundColor=colors[Math.min(score,4)];
    text.textContent=score>0?texts[Math.min(score,4)]:'';
    text.style.color=colors[Math.min(score,4)];
}
</script>
@endsection

