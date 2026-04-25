<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mot de passe oublié - Gestion documentaire qualité</title>
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
            min-height: 100vh;
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

        .form-success {
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.4);
            color: #86efac;
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

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            font-size: .9rem;
            color: var(--muted);
            transition: color .2s;
        }

        .back-link:hover {
            color: var(--accent);
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
                <p class="login-visual-sub">Réinitialisez votre mot de passe en toute sécurité</p>
            </div>
        </div>

        <div class="login-form-section">
            <div class="login-card">
                <div class="card-header">
                    <div class="card-title">Mot de passe oublié</div>
                    <div class="card-sub">Un lien sera envoyé à votre adresse email</div>
                </div>

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    @if(session('status'))
                    <div class="form-success">
                        {{ session('status') }}
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
                    </div>

                    <button type="submit" class="btn-full">Envoyer le lien</button>
                </form>

                <a href="{{ route('login') }}" class="back-link">← Retour à la connexion</a>
            </div>
        </div>
    </div>
</body>
</html>