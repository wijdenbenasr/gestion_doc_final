<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - QMS Doc Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    @yield('styles')
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
            overflow-x: hidden;
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
            background: #f59e0b;
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
            animation: pulse 2s infinite;
        }
        .notification-badge-empty {
            background: var(--muted);
            color: var(--text);
            opacity: 0.7;
        }
        .notification-dropdown {
            display: none;
            position: absolute;
            background-color: #1a2035 !important;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            min-width: 300px;
            max-width: 320px;
            padding: 0;
            z-index: 9999;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            transform: translateX(0) !important;
        }
        .notification-dropdown * {
            color: white !important;
        }
        .notification-dropdown .dropdown-item {
            color: white !important;
            background-color: transparent !important;
        }
        .notification-dropdown .dropdown-item:hover {
            background-color: rgba(255,255,255,0.05) !important;
        }
        .notification-dropdown.active { display: block; }
        .notification-header {
            padding: .85rem 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: .5rem;
            font-weight: 600;
            font-size: .9rem;
            color: var(--text);
        }
        .notification-header i { color: var(--accent); }
        .notification-list {
            max-height: 280px;
            overflow-y: auto;
        }
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
        .notification-item.warning .notification-item-title { color: #f59e0b; }
        .notification-empty {
            padding: 2rem 1rem;
            text-align: center;
            color: var(--muted);
        }
        .notification-empty i {
            font-size: 2rem;
            display: block;
            margin-bottom: .75rem;
            opacity: .4;
        }
        .notification-footer {
            padding: .75rem 1rem;
            border-top: 1px solid var(--border);
            text-align: center;
        }
        .notification-footer-link {
            color: var(--accent);
            font-size: .85rem;
            font-weight: 500;
            transition: opacity .15s;
        }
        .notification-footer-link:hover { opacity: .8; }
        .profile-menu-container { position: relative; }
        .profile-trigger {
            display: inline-flex;
            align-items: center;
            gap: .65rem;
            padding: .3rem .45rem;
            border-radius: .9rem;
            border: 1px solid var(--border);
            background: rgba(15,23,42,0.72);
            color: var(--text);
            cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .profile-trigger:hover,
        .profile-trigger.active {
            border-color: rgba(56,189,248,0.45);
            background: rgba(56,189,248,0.08);
        }
        .profile-trigger-copy {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }
        .profile-trigger-name {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .78rem;
            font-weight: 600;
        }
        .profile-trigger-role {
            font-size: .64rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .profile-chevron {
            width: 12px;
            height: 12px;
            color: var(--muted);
            flex-shrink: 0;
        }
        .profile-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            border-radius: 50%;
            border: 1px solid rgba(56,189,248,0.35);
            background: linear-gradient(135deg, rgba(14,165,233,0.92), rgba(15,23,42,0.95));
            color: #f8fafc;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(2,6,23,0.45);
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-avatar-sm { width: 36px; height: 36px; font-size: .8rem; }
        .profile-avatar-lg { width: 68px; height: 68px; font-size: 1.15rem; }
        .profile-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: .55rem;
            width: min(360px, calc(100vw - 2rem));
            background: linear-gradient(180deg, rgba(15,23,42,0.98), rgba(2,6,23,0.98));
            border: 1px solid var(--border);
            border-radius: 1rem;
            box-shadow: 0 16px 40px rgba(0,0,0,0.55);
            padding: 1rem;
            z-index: 1000;
        }
        .profile-dropdown.active { display: block; }
        .profile-summary {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding-bottom: .9rem;
            border-bottom: 1px solid rgba(148,163,184,0.14);
        }
        .profile-summary-name {
            font-size: .92rem;
            font-weight: 700;
            color: var(--text);
        }
        .profile-summary-role {
            margin-top: .2rem;
            font-size: .66rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--accent);
        }
        .profile-summary-email {
            margin-top: .3rem;
            font-size: .72rem;
            color: var(--muted);
            word-break: break-word;
        }
        .profile-detail-list {
            display: grid;
            gap: .55rem;
            margin-top: .9rem;
        }
        .profile-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: .6rem .7rem;
            border-radius: .8rem;
            border: 1px solid rgba(148,163,184,0.12);
            background: rgba(15,23,42,0.72);
            font-size: .75rem;
        }
        .profile-detail-item span { color: var(--muted); }
        .profile-detail-item strong {
            color: var(--text);
            font-weight: 600;
            text-align: right;
            word-break: break-word;
        }
        .profile-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
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
        .btn-admin-create { border-radius: 999px; padding: .55rem 1.25rem; font-size: .85rem; font-weight: 700; margin-left: auto; }
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
            overflow: visible;
        }
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; gap: .75rem; }
        .card-title { font-size: 1rem; font-weight: 700; text-transform: none; letter-spacing: .05em; }
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
        .stat-label { font-size: .72rem; color: var(--muted); margin-bottom: .25rem; font-weight: 600; }
        .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
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
        table { width: 100%; border-collapse: collapse; font-size: .78rem; overflow: visible; }
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
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .badge-pulse { animation: pulse 2s infinite; }
        .badge-attente { animation: pulse 2s infinite; }
        .badge-warning { background: rgba(234,179,8,0.13); color: #fde68a; }
        .badge-pending { background: rgba(245,158,11,0.2); color: #f59e0b; }
        .badge-validation { background: rgba(56,189,248,0.13); color: #38bdf8; }
        .badge-approved { background: rgba(34,197,94,0.13); color: #86efac; }
        .badge-rejected { background: rgba(239,68,68,0.13); color: #fca5a5; }
        .badge-signed { background: rgba(168,85,247,0.13); color: #c084fc; }
        .badge-danger { background: rgba(239,68,68,0.13); color: #fca5a5; }
        .badge-muted { background: rgba(148,163,184,0.12); color: #d1d5db; }
        .badge-info { background: rgba(14,165,233,0.13); color: #7dd3fc; }
        .pagination { margin-top: .75rem; display: flex; justify-content: flex-end; gap: .25rem; font-size: .75rem; }
        .pagination span, .pagination a { padding: .2rem .45rem; border-radius: .3rem; border: 1px solid transparent; }
        .pagination .active span { border-color: rgba(56,189,248,0.4); background: rgba(56,189,248,0.1); color: var(--accent); }

        .notif-dropdown {
            display: none;
            position: absolute;
            background-color: #1a2035 !important;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 10px;
            min-width: 300px;
            max-width: 320px;
            padding: 0;
            z-index: 9999;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            transform: translateX(0) !important;
        }
        .notif-dropdown * {
            color: white !important;
        }
        .notif-dropdown .dropdown-item {
            color: white !important;
            background-color: transparent !important;
        }
        .notif-dropdown .dropdown-item:hover {
            background-color: rgba(255,255,255,0.05) !important;
        }
        .notif-dropdown.show { display: block; }
        .dropdown-menu {
            display: none;
            position: absolute;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: .6rem;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
            min-width: 180px;
            z-index: 100;
            padding: .25rem 0;
        }
        .dropdown-menu.show { display: block; }
        .dropdown-item {
            display: block;
            padding: .45rem .85rem;
            font-size: .75rem;
            color: var(--text);
            cursor: pointer;
            transition: background .15s;
        }
        .dropdown-item:hover { background: rgba(56,189,248,0.1); }
        .dropdown-item.text-danger { color: var(--danger); }
        .action-menu {
            display: none;
            position: fixed;
            background: #1a2035;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 8px 0;
            z-index: 9999;
            min-width: 160px;
            list-style: none;
            margin: 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        .action-menu .dropdown-item:hover { background: rgba(56,189,248,0.15); }
        .action-menu.show { display: block; }
        .dropdown-divider { border-top: 1px solid var(--border); margin: .25rem 0; }
        .btn-outline-secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }
        .btn-outline-secondary:hover {
            background: rgba(148,163,184,0.1);
            border-color: var(--muted);
        }
        .bg-secondary { background: rgba(148,163,184,0.12); color: #d1d5db; }

        /* Responsive table columns */
        @media (max-width: 1024px) {
            .col-valide, .col-approuve, .col-rejete, .col-signe { display: none; }
        }
        @media (max-width: 768px) {
            .col-ligne, .col-type { display: none; }
        }
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
            .profile-trigger-copy { display: none; }
        }
    </style>
</head>
<body>
<header>
    <div class="header-inner">
        <a href="{{ route('dashboard') }}" class="brand">
            <div class="brand-logo"></div>
            <div>
                <div class="brand-title">Gestion documentaire qualité</div>
                <div class="brand-sub">Plateforme sécurisée de gestion documentaire</div>
            </div>
        </a>

        <nav>
            @auth
                @php
                    $user = auth()->user();
                    $role = $user->role;
                    $accountMenuActive = request()->routeIs('account.profile.*', 'account.password.*');
                @endphp

                @if($role === 'creator')
                    <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Créer
                    </a>
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
                    <a href="{{ route('admin.documents.create') }}" class="btn btn-primary btn-admin-create">
                        <i class="fas fa-plus"></i> Créer
                    </a>
                @endif

                <a href="{{ route('documents.archive') }}" class="nav-link {{ request()->routeIs('documents.archive') ? 'active' : '' }}">Archive</a>

                <div class="dropdown" style="position: relative;">
                    <button type="button" class="notification-bell" onclick="toggleNotifDropdown(event, this)" style="background:transparent;border:1px solid var(--border);border-radius:.5rem;padding:.35rem .5rem;cursor:pointer;">
                        <i class="fas fa-bell"></i>
                        @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="notification-badge">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </button>
                    <div class="notif-dropdown">
                        <div style="padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.1);color:white;font-weight:600;display:flex;justify-content:space-between;">
                            <span><i class="fas fa-bell"></i> Notifications</span>
                            <span class="badge bg-danger">{{ auth()->user()->unreadNotifications->count() }}</span>
                        </div>
                        @forelse(auth()->user()->unreadNotifications->take(5) as $notif)
                            <a href="{{ route('notifications.read', $notif->id) }}" class="dropdown-item py-2 px-3" style="border-bottom:1px solid rgba(255,255,255,0.05);display:block;">
                                <i class="fas fa-file-alt me-2" style="color:#f59e0b;"></i>
                                {{ $notif->data['message'] ?? 'Notification' }}
                                <br>
                                <small style="color:rgba(255,255,255,0.6);">{{ $notif->created_at->diffForHumans() }}</small>
                            </a>
                        @empty
                            <div style="text-align:center; padding: 24px 16px;">
                                <i class="fas fa-bell-slash" style="font-size:2rem;"></i>
                                <p style="color:rgba(255,255,255,0.5); margin-top:8px;">Aucune notification</p>
                            </div>
                        @endforelse
                        <div style="border-top:1px solid rgba(255,255,255,0.1)">
                            <a href="{{ route('notifications.index') }}" style="display:block;text-align:center;padding:10px;color:#3b82f6;font-weight:600;border-top:1px solid rgba(255,255,255,0.1);text-decoration:none;">Voir tout †’</a>
                        </div>
                    </div>
                </div>

                <div class="profile-menu-container">
                    <button type="button" class="profile-trigger {{ $accountMenuActive ? 'active' : '' }}" onclick="toggleProfileMenu(event)" aria-label="Ouvrir le menu profil">
                        @if($user->profile_photo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($user->profile_photo) }}"
                                 style="width:40px;height:40px;border-radius:50%;object-fit:cover;object-position:center;border:2px solid #3b82f6;">
                        @else
                            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:bold;color:white;">
                                {{ strtoupper(substr($user->prenom,0,1)) }}{{ strtoupper(substr($user->name,0,1)) }}
                            </div>
                        @endif
                        <span class="profile-trigger-copy">
                            <span class="profile-trigger-name">{{ $user->full_name }}</span>
                            <span class="profile-trigger-role">{{ $user->role_label }}</span>
                        </span>
                        <svg class="profile-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-summary">
                            @if($user->profile_photo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($user->profile_photo) }}"
                                     style="width:110px;height:110px;border-radius:50%;object-fit:cover;object-position:center;border:3px solid #3b82f6;">
                            @else
                                <div style="width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,#1d4ed8,#3b82f6);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:bold;color:white;">
                                    {{ strtoupper(substr($user->prenom,0,1)) }}{{ strtoupper(substr($user->name,0,1)) }}
                                </div>
                            @endif
                            <div>
                                <div class="profile-summary-name"><i class="fa fa-user"></i> {{ $user->full_name }}</div>
                                <div class="profile-summary-role">{{ $user->role_label }}</div>
                                <div class="profile-summary-email"><i class="fa fa-envelope"></i> {{ $user->email }}</div>
                            </div>
                        </div>

                        <div class="profile-detail-list">
                            <div class="profile-detail-item">
                                <span>Nom</span>
                                <strong>{{ $user->name ?: '-' }}</strong>
                            </div>
                            <div class="profile-detail-item">
                                <span>Prénom</span>
                                <strong>{{ $user->prenom ?: '-' }}</strong>
                            </div>
                            <div class="profile-detail-item">
                                <span>CIN</span>
                                <strong>{{ $user->cin ?: '-' }}</strong>
                            </div>
                            <div class="profile-detail-item">
                                <span>Matricule</span>
                                <strong>{{ $user->matricule ?: '-' }}</strong>
                            </div>
                        </div>

                        <hr style="margin:.75rem 0;border:none;border-top:1px solid #e5e7eb;">

                        <div class="profile-actions">
                            <a href="{{ route('account.profile.show') }}" class="btn btn-primary btn-sm" onclick="closeProfileMenu()">Mon profil</a>
                            <a href="{{ route('account.password.edit') }}" class="btn btn-ghost btn-sm" onclick="closeProfileMenu()">Changer le mot de passe</a>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Déconnexion</button>
                </form>
            @endauth

            @guest
                <a href="{{ route('login') }}" class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}">Connexion</a>
                <a href="{{ route('register') }}" class="btn btn-sm">Créer un compte</a>
            @endguest
        </nav>
    </div>
</header>

<main>
    <div class="main-inner">
        @if(session('success') || session('status'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                {{ session('success') ?? session('status') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i> {{ session('error') }}
            </div>
        @endif
        @if(session('warning'))
            <div class="alert alert-error" style="background:rgba(234,179,8,0.08);border-color:rgba(234,179,8,0.4);color:#fde68a;">
                <i class="fas fa-exclamation-triangle"></i> {{ session('warning') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i> {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </div>
</main>

<footer>
    <div class="footer-inner">
        <span> {{ date('Y') }} Gestion documentaire qualité</span>
    </div>
</footer>

<script>
function toggleNotifications(event) {
    event.preventDefault();
    event.stopPropagation();
    closeProfileMenu();
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

function toggleProfileMenu(event) {
    event.preventDefault();
    event.stopPropagation();
    closeDropdown();
    const dropdown = document.getElementById('profileDropdown');
    if (!dropdown) {
        return;
    }

    dropdown.classList.toggle('active');
}

function closeProfileMenu() {
    const dropdown = document.getElementById('profileDropdown');
    if (!dropdown) {
        return;
    }

    dropdown.classList.remove('active');
}

function toggleMenu(btn) {
    const menu = btn.nextElementSibling;
    const rect = btn.getBoundingClientRect();
    menu.style.top = (rect.bottom + 5) + 'px';
    menu.style.left = (rect.left - 100) + 'px';
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    const evt = window.event;
    if (evt) evt.stopPropagation();
}

function toggleNotifDropdown(event, btn) {
    const evt = (event && event.target) ? event : window.event;
    if (evt) evt.stopPropagation();
    const dropdown = btn.nextElementSibling;
    document.querySelectorAll('.notif-dropdown').forEach(function(d) {
        if (d !== dropdown) d.style.display = 'none';
    });
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('click', function() {
    document.querySelectorAll('.notif-dropdown').forEach(function(d) {
        d.style.display = 'none';
    });
});

document.addEventListener('click', function(e) {
    if (!e.target.matches('button') && !e.target.closest('.action-menu') && !e.target.closest('.modal')) {
        document.querySelectorAll('.action-menu').forEach(m => m.style.display = 'none');
    }
});

function toggleRowActions(event, btn) {
    const evt = (event && event.target) ? event : window.event;
    if (!btn) btn = event;
    var menu = btn.nextElementSibling;
    var isOpen = menu.style.display === 'block';
    document.querySelectorAll('.row-actions-menu').forEach(function(m) { m.style.display = 'none'; });
    if (!isOpen) { menu.style.display = 'block'; }
    if (evt) evt.stopPropagation();
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationsDropdown');
    const bell = event.target.closest('.notification-bell');
    const profileDropdown = document.getElementById('profileDropdown');
    const profileTrigger = event.target.closest('.profile-trigger');
    if (!dropdown) {
        closeProfileMenu();
    } else if (!bell && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }

    if (profileDropdown && !profileTrigger && !profileDropdown.contains(event.target)) {
        profileDropdown.classList.remove('active');
    }
});
</script>
@include('components.delete-modal')
@yield('modals')
@yield('scripts')
</body>
</html>


