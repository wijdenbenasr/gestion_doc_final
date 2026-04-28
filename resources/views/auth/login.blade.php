<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion - Gestion documentaire qualité</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #020617;
            --accent: #0ea5e9;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --border: #1f2937;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #1e293b 0%, #020617 50%, #000 100%);
            color: var(--text);
        }
        a { color: inherit; text-decoration: none; }

        .login-page {
            display: flex;
            flex-direction: row;
            width: 100vw;
            height: 100vh;
        }

        .login-visual {
            flex: 0 0 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(14,165,233,0.08) 0%, transparent 50%);
        }

        .login-visual-content {
            text-align: center;
            padding: 2rem;
        }

        .login-visual-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 1rem;
        }

        .login-visual-divider {
            width: 60px;
            height: 3px;
            background: var(--accent);
            margin: 0 auto 1rem;
            border-radius: 2px;
        }

        .login-visual-sub {
            font-size: 1.1rem;
            color: var(--muted);
            line-height: 1.6;
        }

        .login-form-section {
            flex: 0 0 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            width: 100%;
            min-width: 400px;
            max-width: 420px;
            background: linear-gradient(135deg, rgba(15,23,42,0.98), rgba(2,6,23,0.98));
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(0,0,0,0.7);
            padding: 2rem;
        }

        .card-header {
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: .25rem;
        }

        .card-sub {
            font-size: .82rem;
            color: var(--muted);
        }

        .form-stack {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
        }

        .form-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.4);
            color: #fca5a5;
            padding: .75rem 1rem;
            border-radius: .5rem;
            font-size: .85rem;
            margin-bottom: 1rem;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: .45rem;
        }

        .field label {
            font-size: .85rem;
            color: var(--muted);
            font-weight: 500;
        }

        .field input {
            height: 45px;
            padding: 0 .85rem;
            border-radius: .5rem;
            border: 1px solid var(--border);
            background: rgba(2,6,23,0.9);
            color: var(--text);
            font-size: .9rem;
            transition: border-color .2s, box-shadow .2s;
        }

        .field input.error {
            border-color: rgba(239,68,68,0.7);
        }

        .field input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14,165,233,0.15);
        }

        .field input::placeholder {
            color: var(--muted);
            opacity: .5;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 40px;
            padding-left: .85rem;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color .2s;
        }

        .password-toggle:hover {
            color: var(--text);
        }

        .password-toggle svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }

        .password-toggle .eye-slash {
            display: none;
        }

        .options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: .25rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-size: .85rem;
            color: var(--muted);
            cursor: pointer;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
        }

        .forgot-link {
            font-size: .85rem;
            color: var(--accent);
            transition: opacity .2s;
        }

        .forgot-link:hover {
            opacity: .8;
        }

        .btn-full {
            width: 100%;
            height: 45px;
            margin-top: 1.3rem;
            border-radius: .5rem;
            border: 1px solid rgba(14,165,233,0.5);
            background: linear-gradient(135deg, rgba(14,165,233,0.25), rgba(14,165,233,0.1));
            color: var(--text);
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .25s;
        }

        .btn-full:hover {
            background: linear-gradient(135deg, rgba(14,165,233,0.35), rgba(14,165,233,0.2));
            border-color: rgba(14,165,233,0.7);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(14,165,233,0.25);
        }

        @media (max-width: 900px) {
            .login-page {
                flex-direction: column;
            }
            .login-visual {
                padding: 2.5rem 2rem 2rem;
            }
            .login-form-section {
                padding: 2rem;
            }
            .login-card {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-visual">
            <div class="login-visual-content">
                <h1 class="login-visual-title">Gestion documentaire qualité</h1>
                <div class="login-visual-divider"></div>
                <p class="login-visual-sub">Accédez à vos documents en toute sécurité</p>
            </div>
        </div>

        <div class="login-form-section">
            <div class="login-card">
                <div class="card-header">
                    <div class="card-title">Connexion</div>
                    <div class="card-sub">Accédez à votre espace sécurisé</div>
                </div>

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    @if(session('success') || session('status'))
                        <div class="form-success" style="margin-bottom:1rem;padding:1rem;border-radius:.75rem;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.4);color:#bbf7d0;">
                            {{ session('success') ?? session('status') }}
                        </div>
                    @endif
                    @if($errors->any())
                    <div class="form-error">
                        {{ $errors->first('email') }}
                    </div>
                    @endif
                    <div class="form-stack">
                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="exemple@email.com" required autofocus>
                        </div>

                        <div class="field">
                            <label for="password">Mot de passe</label>
                            <div class="password-wrapper">
                                <input id="password" type="password" name="password" placeholder="Votre mot de passe" required>
                                <button type="button" class="password-toggle" id="togglePassword" aria-label="Afficher le mot de passe">
                                    <svg class="eye-open" viewBox="0 0 24 24">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg class="eye-slash" viewBox="0 0 24 24">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a11 11 0 0 1-3.94-8.18"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="options-row">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                <span>Se souvenir de moi</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="forgot-link">Mot de passe oublié ?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-full">Se connecter</button>
                </form>

                <div style="text-align:center; margin-top:1rem;">
                    Pas encore de compte ?
                    <a href="{{ route('register') }}" style="color:#3b82f6;">Créer un compte</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const input = document.getElementById('password');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        this.querySelector('.eye-open').style.display = isPassword ? 'none' : 'block';
        this.querySelector('.eye-slash').style.display = isPassword ? 'block' : 'none';
    });
    </script>
</body>
</html>


