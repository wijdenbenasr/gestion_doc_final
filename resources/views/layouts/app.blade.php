<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - QMS Doc Control</title>
    <style>
        :root {
            --bg: #0f172a;
            --panel: #020617;
            --accent: #0ea5e9;
            --accent-soft: rgba(14,165,233,0.12);
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #eab308;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --border: #1f2937;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top, #1e293b 0, #020617 50%, #000 100%);
            color: var(--text);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        header {
            padding: .85rem 2rem;
            border-bottom: 1px solid var(--border);
            background: rgba(2,6,23,0.85);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .brand { display: flex; align-items: center; gap: .65rem; }
        .brand-logo {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #38bdf8 0, #0f172a 65%);
            box-shadow: 0 0 24px rgba(56,189,248,0.55);
        }
        .brand-title { font-size: .9rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
        .brand-sub { font-size: .68rem; color: var(--muted); }
        nav { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .nav-link {
            padding: .3rem .7rem;
            border-radius: 999px;
            font-size: .78rem;
            color: var(--muted);
            border: 1px solid transparent;
            transition: color .15s, border-color .15s;
        }
        .nav-link:hover { color: var(--text); }
        .nav-link.active { background: var(--accent-soft); color: var(--accent); border-color: rgba(56,189,248,0.3); }
        .user-chip { display: flex; align-items: center; gap: .4rem; font-size: .75rem; color: var(--muted); }
        .role-tag {
            padding: .15rem .5rem;
            border-radius: 999px;
            border: 1px solid rgba(56,189,248,0.35);
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--accent);
        }
        .notifications-container { position: relative; display: flex; align-items: center; }
        .notification-bell {
            background: transparent;
            border: 1px solid var(--border);
            border-radius: .5rem;
            padding: .35rem .5rem;
            cursor: pointer;
            font-size: 1rem;
            position: relative;
            transition: border-color .15s, background .15s;
            display: flex;
            align-items: center;
        }
        .notification-bell:hover { border-color: var(--accent); background: rgba(56,189,248,0.08); }
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: 999px;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .62rem;
            font-weight: 700;
            border: 2px solid var(--bg);
        }
        .notification-badge-empty {
            background: var(--muted);
            color: var(--text);
            opacity: 0.7;
        }
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: .5rem;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: .75rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            min-width: 320px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
        }
        .notification-dropdown.active { display: block; }
        .notification-item {
            padding: .7rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: .78rem;
            transition: background .15s;
        }
        .notification-item:hover { background: rgba(56,189,248,0.08); }
        .notification-item:last-child { border-bottom: none; }
        .notification-item-title { font-weight: 600; color: var(--text); margin-bottom: .25rem; }
        .notification-item-meta { color: var(--muted); font-size: .72rem; }
        .notification-item.urgent .notification-item-title { color: var(--danger); }
        .notification-item.warning .notification-item-title { color: var(--warning); }
        .notification-empty {
            padding: 1.5rem 1rem;
            text-align: center;
            color: var(--muted);
            font-size: .78rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .4rem .85rem;
            border-radius: .5rem;
            border: 1px solid rgba(148,163,184,0.35);
            background: linear-gradient(135deg, rgba(56,189,248,0.14), rgba(15,23,42,0.95));
            color: var(--text);
            font-size: .78rem;
            font-weight: 500;
            cursor: pointer;
            transition: opacity .15s;
        }
        .btn:hover { opacity: .85; }
        .btn-ghost { background: transparent; border-style: dashed; }
        .btn-danger { background: linear-gradient(135deg, rgba(239,68,68,0.18), rgba(15,23,42,0.95)); border-color: rgba(239,68,68,0.4); }
        .btn-sm { padding: .25rem .55rem; font-size: .72rem; }
        .btn-primary { background: linear-gradient(135deg, rgba(14,165,233,0.28), rgba(15,23,42,0.95)); border-color: rgba(14,165,233,0.6); font-weight: 600; }
        main { padding: 1.5rem 2rem; }
        .main-inner { max-width: 1280px; margin: 0 auto 4rem; }
        .alert {
            border-radius: .75rem;
            padding: .65rem 1rem;
            font-size: .78rem;
            border: 1px solid transparent;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .alert-success { background: rgba(34,197,94,0.08); border-color: rgba(34,197,94,0.4); color: #bbf7d0; }
        .alert-error { background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.4); color: #fecaca; }
        .card {
            background: linear-gradient(135deg, rgba(15,23,42,0.98), rgba(2,6,23,0.98));
            border-radius: 1.2rem;
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px rgba(0,0,0,0.7);
            padding: 1.4rem 1.6rem;
            margin-bottom: 1.25rem;
        }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; gap: .75rem; }
        .card-title { font-size: 1rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .card-sub { font-size: .76rem; color: var(--muted); margin-top: .15rem; }
        .cards-row { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.25rem; }
        .stat-card {
            border-radius: 1rem;
            border: 1px solid var(--border);
            background: rgba(15,23,42,0.85);
            padding: .85rem 1rem;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .stat-card:hover {
            border-color: var(--accent);
            background: rgba(15,23,42,0.95);
        }
        .stat-card.active {
            border-color: var(--accent);
            background: rgba(59,130,246,0.1);
        }
        .stat-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-bottom: .25rem; }
        .stat-value { font-size: 1.5rem; font-weight: 700; line-height: 1; }
        .stat-meta { font-size: .68rem; color: var(--muted); margin-top: .2rem; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .85rem 1.25rem; }
        .field { display: flex; flex-direction: column; gap: .2rem; }
        .field label { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; }
        .field input, .field select, .field textarea {
            border-radius: .5rem;
            border: 1px solid var(--border);
            background: rgba(2,6,23,0.9);
            padding: .5rem .65rem;
            color: var(--text);
            font-size: .82rem;
        }
        .field input:focus, .field select:focus, .field textarea:focus {
            outline: none;
            border-color: rgba(56,189,248,0.6);
            box-shadow: 0 0 0 2px rgba(56,189,248,0.15);
        }
        .form-actions { margin-top: 1.1rem; display: flex; justify-content: flex-end; gap: .6rem; }
        table { width: 100%; border-collapse: collapse; font-size: .78rem; }
        th, td { padding: .55rem .5rem; border-bottom: 1px solid rgba(31,41,55,0.8); text-align: left; }
        th { font-size: .68rem; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); font-weight: 600; }
        tr:hover td { background: rgba(14,165,233,0.03); }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-size: .67rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            white-space: nowrap;
        }
        .badge-success { background: rgba(34,197,94,0.13); color: #86efac; }
        .badge-warning { background: rgba(234,179,8,0.13); color: #fde68a; }
        .badge-danger { background: rgba(239,68,68,0.13); color: #fca5a5; }
        .badge-muted { background: rgba(148,163,184,0.12); color: #d1d5db; }
        .badge-info { background: rgba(14,165,233,0.13); color: #7dd3fc; }
        .pagination { margin-top: .75rem; display: flex; justify-content: flex-end; gap: .25rem; font-size: .75rem; }
        .pagination span, .pagination a { padding: .2rem .45rem; border-radius: .3rem; border: 1px solid transparent; }
        .pagination .active span { border-color: rgba(56,189,248,0.4); background: rgba(56,189,248,0.1); color: var(--accent); }
        footer { padding: 1rem 2rem 1.5rem; border-top: 1px solid var(--border); font-size: .72rem; color: var(--muted); }
        .footer-inner { max-width: 1280px; margin: 0 auto; display: flex; justify-content: space-between; gap: 1rem; }
        @media (max-width: 900px) {
            .cards-row { grid-template-columns: repeat(2, 1fr); }
            .form-grid { grid-template-columns: 1fr; }
            header, main, footer { padding-inline: 1rem; }
        }
        @media (max-width: 600px) {
            .cards-row { grid-template-columns: 1fr; }
            .header-inner, .footer-inner { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<header>
    <div class="header-inner">
        <a href="{{ route('dashboard') }}" class="brand">
            <div class="brand-logo"></div>
            <div>
                <div class="brand-title">QMS Doc Control</div>
                <div class="brand-sub">Gestion electronique des documents</div>
            </div>
        </a>

        <nav>
            @auth
                @php $role = auth()->user()->role; @endphp

                @if($role === 'creator')
                    <a href="{{ route('documents.creator.index') }}" class="nav-link {{ request()->routeIs('documents.creator.*', 'documents.create', 'documents.edit') ? 'active' : '' }}">Mes documents</a>
                @endif

                @if($role === 'validator')
                    <a href="{{ route('workflow.validator.index') }}" class="nav-link {{ request()->routeIs('workflow.validator.*') ? 'active' : '' }}">A valider</a>
                @endif

                @if($role === 'approver')
                    <a href="{{ route('workflow.approver.index') }}" class="nav-link {{ request()->routeIs('workflow.approver.*') ? 'active' : '' }}">A approuver</a>
                @endif

                @if($role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.documents.codification') }}" class="nav-link {{ request()->routeIs('admin.documents.codification') ? 'active' : '' }}">Codification</a>
                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">Utilisateurs</a>
                @endif

                <a href="{{ route('documents.archive') }}" class="nav-link {{ request()->routeIs('documents.archive') ? 'active' : '' }}">Archive</a>

                <div class="notifications-container">
                    @php
                        $unreadCount = 0;
                        $notificationUrl = '#';
                        $hasDropdown = false;
                        $notifications = [];

                        if (auth()->check()) {
                            $user = auth()->user();
                            $role = $user->role;

                            if ($role === 'creator') {
                                $notificationUrl = route('documents.creator.index', ['status' => 'rejected']);
                                $hasDropdown = true;
                                $unreadCount = \App\Models\Document::where('created_by', $user->id)
                                    ->where('status', 'rejected')
                                    ->count();

                                $rejectedDocuments = \App\Models\Document::where('created_by', $user->id)
                                    ->where('status', 'rejected')
                                    ->latest()
                                    ->limit(3)
                                    ->get();

                                foreach ($rejectedDocuments as $doc) {
                                    $notifications[] = [
                                        'title' => 'Document rejete : '.$doc->name,
                                        'meta' => 'A corriger et renvoyer',
                                        'type' => 'urgent',
                                        'url' => $notificationUrl,
                                    ];
                                }
                            } elseif ($role === 'validator') {
                                $notificationUrl = route('workflow.validator.index', ['filter' => 'pending']);
                                $hasDropdown = true;
                                $unreadCount = \App\Models\Document::where('status', 'in_validation')
                                    ->where('current_role', 'validator')
                                    ->count();

                                $pendingDocuments = \App\Models\Document::with('creator')
                                    ->where('status', 'in_validation')
                                    ->where('current_role', 'validator')
                                    ->latest()
                                    ->limit(3)
                                    ->get();

                                foreach ($pendingDocuments as $doc) {
                                    $metaParts = [
                                        $doc->code ?: 'Sans code',
                                        $doc->creator->name ?? 'Createur inconnu',
                                    ];

                                    if ($doc->deadline) {
                                        $metaParts[] = 'Deadline '.$doc->deadline->format('d/m/Y');
                                    }

                                    $notifications[] = [
                                        'title' => 'Validation requise : '.$doc->name,
                                        'meta' => implode(' | ', $metaParts),
                                        'type' => $doc->deadline && $doc->deadline->isPast()
                                            ? 'urgent'
                                            : ($doc->deadline && $doc->deadline->isBefore(now()->addDays(2)) ? 'warning' : ''),
                                        'url' => $notificationUrl,
                                    ];
                                }
                            } elseif ($role === 'approver') {
                                $notificationUrl = route('workflow.approver.index', ['filter' => 'pending']);
                                $hasDropdown = true;
                                $unreadCount = \App\Models\Document::where('status', 'in_validation')
                                    ->where('current_role', 'approver')
                                    ->count();

                                $pendingDocuments = \App\Models\Document::with('creator')
                                    ->where('status', 'in_validation')
                                    ->where('current_role', 'approver')
                                    ->latest()
                                    ->limit(3)
                                    ->get();

                                foreach ($pendingDocuments as $doc) {
                                    $metaParts = [
                                        $doc->code ?: 'Sans code',
                                        $doc->creator->name ?? 'Createur inconnu',
                                    ];

                                    if ($doc->deadline) {
                                        $metaParts[] = 'Deadline '.$doc->deadline->format('d/m/Y');
                                    }

                                    $notifications[] = [
                                        'title' => 'Approbation requise : '.$doc->name,
                                        'meta' => implode(' | ', $metaParts),
                                        'type' => $doc->deadline && $doc->deadline->isPast()
                                            ? 'urgent'
                                            : ($doc->deadline && $doc->deadline->isBefore(now()->addDays(2)) ? 'warning' : ''),
                                        'url' => $notificationUrl,
                                    ];
                                }
                            } elseif ($role === 'admin') {
                                $pending = \App\Models\Document::where('status', 'pending_codification')->count();
                                $pendingUsers = \App\Models\User::where('is_admin_approved', false)->count();
                                $unreadCount = $pending + $pendingUsers;
                                $hasDropdown = true;

                                $pendingDocuments = \App\Models\Document::with('creator')
                                    ->where('status', 'pending_codification')
                                    ->latest()
                                    ->limit(2)
                                    ->get();

                                foreach ($pendingDocuments as $doc) {
                                    $notifications[] = [
                                        'title' => 'Codification requise : '.$doc->name,
                                        'meta' => 'Par '.($doc->creator->name ?? 'Inconnu'),
                                        'type' => 'urgent',
                                        'url' => route('admin.documents.codification'),
                                    ];
                                }

                                $pendingUsersList = \App\Models\User::where('is_admin_approved', false)
                                    ->latest()
                                    ->limit(2)
                                    ->get();

                                foreach ($pendingUsersList as $pendingUser) {
                                    $notifications[] = [
                                        'title' => 'Compte en attente : '.$pendingUser->name,
                                        'meta' => 'Validation admin requise',
                                        'type' => 'warning',
                                        'url' => route('admin.users.pending'),
                                    ];
                                }
                            }
                        }
                    @endphp
                    @if($hasDropdown)
                        <button class="notification-bell" onclick="toggleNotifications(event)" title="Notifications ({{ $unreadCount }})">
                            🔔
                            @if($unreadCount > 0)
                                <span class="notification-badge">{{ min($unreadCount, 9) }}{{ $unreadCount > 9 ? '+' : '' }}</span>
                            @endif
                        </button>
                        <div class="notification-dropdown" id="notificationsDropdown">
                            @if(count($notifications) > 0)
                                @foreach($notifications as $notif)
                                    <a href="{{ $notif['url'] }}" class="notification-item {{ $notif['type'] }}" onclick="closeDropdown()">
                                        <div class="notification-item-title">{{ $notif['title'] }}</div>
                                        <div class="notification-item-meta">{{ $notif['meta'] }}</div>
                                    </a>
                                @endforeach
                            @else
                                <div class="notification-empty">Aucune notification</div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="user-chip">
                    <span>{{ auth()->user()->name }}</span>
                    <span class="role-tag">{{ $role }}</span>
                </div>

                <a href="{{ route('account.password.edit') }}" class="nav-link {{ request()->routeIs('account.password.*') ? 'active' : '' }}">Mot de passe</a>

                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Deconnexion</button>
                </form>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">Connexion</a>
                <a href="{{ route('register') }}" class="btn btn-sm">Creer un compte</a>
            @endguest
        </nav>
    </div>
</header>

<main>
    <div class="main-inner">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        @yield('content')
    </div>
</main>

<footer>
    <div class="footer-inner">
        <span>Copyright {{ date('Y') }} QMS Doc Control</span>
        <span>Audit trail | RBAC | Archivage auto</span>
    </div>
</footer>

<script>
function toggleNotifications(event) {
    event.preventDefault();
    event.stopPropagation();
    const dropdown = document.getElementById('notificationsDropdown');
    if (!dropdown) {
        return;
    }

    dropdown.classList.toggle('active');
}

function closeDropdown() {
    const dropdown = document.getElementById('notificationsDropdown');
    if (!dropdown) {
        return;
    }

    dropdown.classList.remove('active');
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationsDropdown');
    const bell = event.target.closest('.notification-bell');
    if (!dropdown) {
        return;
    }

    if (!bell && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});
</script>
</body>
</html>
