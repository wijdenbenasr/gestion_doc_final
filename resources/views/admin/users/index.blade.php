@extends('layouts.app')

@section('title', 'Gestion des utilisateurs')

@section('content')
@php
    $roles = \App\Models\User::ROLES;
    $no_search = $filters['search'] === '';
    $is_all = $no_search && $filters['role'] === '' && $filters['approval'] === '' && $filters['filter'] === '';
    $is_pending = $no_search && $filters['approval'] === 'pending' && $filters['role'] === '' && $filters['filter'] === '';
    $is_unverified = $no_search && $filters['filter'] === 'unverified' && $filters['role'] === '' && $filters['approval'] === '';
    $is_admin_role = $no_search && $filters['role'] === 'admin' && $filters['approval'] === '' && $filters['filter'] === '';
    $is_creator_role = $no_search && $filters['role'] === 'creator' && $filters['approval'] === '' && $filters['filter'] === '';
    $is_validator_role = $no_search && $filters['role'] === 'validator' && $filters['approval'] === '' && $filters['filter'] === '';
    $is_approver_role = $no_search && $filters['role'] === 'approver' && $filters['approval'] === '' && $filters['filter'] === '';

    $active_filters = [];
    if ($filters['search'] !== '') {
        $active_filters[] = 'Recherche: '.$filters['search'];
    }
    if ($filters['role'] !== '') {
        $active_filters[] = 'Role: '.($roles[$filters['role']] ?? $filters['role']);
    }
    if ($filters['approval'] === 'approved') {
        $active_filters[] = 'Statut: Approuves';
    }
    if ($filters['approval'] === 'pending') {
        $active_filters[] = 'Statut: En attente';
    }
    if ($filters['filter'] === 'unverified') {
        $active_filters[] = 'Verification: Codes en attente';
    }
    $has_active_filters = count($active_filters) > 0;
@endphp
<div class="cards-row">
    <a href="{{ route('admin.users.index') }}" class="stat-card {{ $is_all ? 'active' : '' }}">
        <div class="stat-label">Utilisateurs</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-meta">Comptes dans la plateforme</div>
    </a>
    <a href="{{ route('admin.users.index', ['approval' => 'pending']) }}" class="stat-card {{ $is_pending ? 'active' : '' }}">
        <div class="stat-label">En attente</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $stats['pending_approval'] }}</div>
        <div class="stat-meta">A approuver par l admin</div>
    </a>
    <a href="{{ route('admin.users.index', ['filter' => 'unverified']) }}" class="stat-card {{ $is_unverified ? 'active' : '' }}">
        <div class="stat-label">Codes a verifier</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $stats['awaiting_email_verification'] }}</div>
        <div class="stat-meta">Comptes approuves non verifies</div>
    </a>
    <a href="{{ route('admin.users.index', ['role' => 'admin']) }}" class="stat-card {{ $is_admin_role ? 'active' : '' }}">
        <div class="stat-label">Admins</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['admins'] }}</div>
        <div class="stat-meta">Acces d administration</div>
    </a>
    <a href="{{ route('admin.users.index', ['role' => 'creator']) }}" class="stat-card {{ $is_creator_role ? 'active' : '' }}">
        <div class="stat-label">Createurs</div>
        <div class="stat-value">{{ $stats['creators'] }}</div>
        <div class="stat-meta">Auteurs de documents</div>
    </a>
    <a href="{{ route('admin.users.index', ['role' => 'validator']) }}" class="stat-card {{ $is_validator_role ? 'active' : '' }}">
        <div class="stat-label">Validateurs</div>
        <div class="stat-value">{{ $stats['validators'] }}</div>
        <div class="stat-meta">Comptes validateurs</div>
    </a>
    <a href="{{ route('admin.users.index', ['role' => 'approver']) }}" class="stat-card {{ $is_approver_role ? 'active' : '' }}">
        <div class="stat-label">Approbateurs</div>
        <div class="stat-value" style="color:#14b8a6;">{{ $stats['approvers'] }}</div>
        <div class="stat-meta">Comptes approbateurs</div>
    </a>
</div>

@php
    $open_quick_create = old('form_context') === 'quick-create-user';
@endphp
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Creation rapide</div>
            <div class="card-sub">Ajoutez un compte depuis l interface admin et activez-le immediatement si besoin.</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <button
                type="button"
                id="quick-create-toggle"
                class="btn btn-primary btn-sm"
                style="gap:.35rem;"
                aria-controls="quick-create-panel"
                aria-expanded="{{ $open_quick_create ? 'true' : 'false' }}"
                onclick="toggleQuickCreatePanel()"
            >
                <i class="fa fa-plus"></i>
                <span id="quick-create-label">{{ $open_quick_create ? 'Masquer creation rapide' : 'Creation rapide' }}</span>
                <span
                    id="quick-create-icon"
                    aria-hidden="true"
                    style="display:inline-block;transition:transform .2s ease;transform:rotate({{ $open_quick_create ? '180deg' : '0deg' }});"
                >▼</span>
            </button>
            <a href="{{ route('admin.users.pending') }}" class="btn btn-ghost btn-sm">Comptes en attente</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">Dashboard</a>
        </div>
    </div>

    <div id="quick-create-panel" @if(! $open_quick_create) hidden @endif>
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <input type="hidden" name="form_context" value="quick-create-user">
            <div class="form-grid">
                <div class="field">
                    <label for="create_name">Nom</label>
                    <input id="create_name" type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="field">
                    <label for="create_prenom">Prenom</label>
                    <input id="create_prenom" type="text" name="prenom" value="{{ old('prenom') }}">
                </div>
                <div class="field">
                    <label for="create_email">Email</label>
                    <input id="create_email" type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="field">
                    <label for="create_role">Role</label>
                    <select id="create_role" name="role" required>
                        <option value="">Choisir un role</option>
                        @foreach($roles as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}" @selected(old('role') === $roleValue)>{{ $roleLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="create_cin">CIN</label>
                    <input id="create_cin" type="text" name="cin" value="{{ old('cin') }}" maxlength="8" minlength="8" pattern="[01]\d{7}" inputmode="numeric" title="Le CIN doit contenir exactement 8 chiffres et commencer par 0 ou 1" placeholder="Ex: 01234567" required>
                    <small style="color:#64748b;font-size:0.78rem;">8 chiffres, commençant par 0 ou 1</small>
                </div>
                <div class="field">
                    <label for="create_matricule">Matricule</label>
                    <input id="create_matricule" type="text" name="matricule" value="{{ old('matricule') }}">
                </div>
                <div class="field">
                    <label for="create_password">Mot de passe</label>
                    <input id="create_password" type="password" name="password" minlength="8" title="Le mot de passe doit contenir au moins 8 caractères" required>
                    <small style="color:#64748b;font-size:0.78rem;">Minimum 8 caractères</small>
                </div>
                <div class="field">
                    <label for="create_password_confirmation">Confirmation du mot de passe</label>
                    <input id="create_password_confirmation" type="password" name="password_confirmation" minlength="8" required>
                </div>
            </div>

            <label style="display:flex;gap:.45rem;align-items:center;font-size:.78rem;margin-top:1rem;">
                <input type="checkbox" name="is_admin_approved" value="1" @checked(old('is_admin_approved'))>
                <span>Approuver ce compte a la creation</span>
            </label>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Créer l utilisateur</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleQuickCreatePanel() {
    const panel = document.getElementById('quick-create-panel');
    const button = document.getElementById('quick-create-toggle');
    const label = document.getElementById('quick-create-label');
    const icon = document.getElementById('quick-create-icon');

    if (!panel || !button) {
        return;
    }

    const shouldShow = panel.hidden;
    panel.hidden = !shouldShow;
    button.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
    if (label) {
        label.textContent = shouldShow ? 'Masquer creation rapide' : 'Creation rapide';
    }
    if (icon) {
        icon.style.transform = shouldShow ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}
</script>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Gestion des utilisateurs</div>
            <div class="card-sub">Recherchez, modifiez, approuvez, renvoyez le code ou supprimez un compte.</div>
            @if($has_active_filters)
                <div style="font-size:.72rem;color:var(--accent);margin-top:.35rem;">Filtre actif: {{ implode(' | ', $active_filters) }}</div>
            @endif
        </div>
        @if($has_active_filters)
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Reinitialiser</a>
        @endif
    </div>

    <form method="GET" style="display:flex;gap:.6rem;align-items:end;flex-wrap:wrap;margin-bottom:1rem;">
        <div class="field" style="min-width:220px;">
            <label for="search">Recherche</label>
            <input id="search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Nom, email, CIN, matricule">
        </div>
        <div class="field">
            <label for="role">Role</label>
            <select id="role" name="role">
                <option value="">Tous</option>
                @foreach($roles as $roleValue => $roleLabel)
                    <option value="{{ $roleValue }}" @selected($filters['role'] === $roleValue)>{{ $roleLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="approval">Statut</label>
            <select id="approval" name="approval">
                <option value="">Tous</option>
                <option value="approved" @selected($filters['approval'] === 'approved')>Approuves</option>
                <option value="pending" @selected($filters['approval'] === 'pending')>En attente</option>
            </select>
        </div>
        <div class="field">
            <label for="filter">Verification</label>
            <select id="filter" name="filter">
                <option value="">Tous</option>
                <option value="unverified" @selected($filters['filter'] === 'unverified')>Codes en attente</option>
            </select>
        </div>
        <button type="submit" class="btn btn-sm">Filtrer</button>
        @if($has_active_filters)
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Effacer filtres</a>
        @endif
    </form>

    <div style="overflow-x:auto;">
        <table>
            <thead>
            <tr>
                <th>Utilisateur</th>
                <th>Coordonnees</th>
                <th>Role</th>
                <th>Statut</th>
                <th>Dates</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $user->name }} {{ $user->prenom }}</div>
                        <div style="font-size:.72rem;color:var(--muted);">CIN: {{ $user->cin ?: '-' }} | Matricule: {{ $user->matricule ?: '-' }}</div>
                    </td>
                    <td>
                        <div>{{ $user->email }}</div>
                        <div style="font-size:.72rem;color:var(--muted);">Email {{ $user->email_verified_at ? 'verifie' : 'non verifie' }}</div>
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $user->role_label }}</span>
                    </td>
                    <td>
                        @if($user->is_admin_approved)
                            <span class="badge badge-success">Approuve</span>
                        @else
                            <span class="badge badge-warning">En attente</span>
                        @endif
                    </td>
                    <td style="font-size:.72rem;">
                        <div>Inscrit: {{ $user->created_at->format('d/m/Y H:i') }}</div>
                        <div>Approuve: {{ $user->admin_approved_at?->format('d/m/Y H:i') ?? '-' }}</div>
                    </td>
                    <td>
                        <div style="display:flex;gap:.35rem;flex-wrap:wrap;align-items:flex-start;">
                            @if($user->is_admin_approved && ! $user->email_verified_at)
                                <form method="POST" action="{{ route('admin.users.resend_code', $user) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">Renvoyer code</button>
                                </form>
                            @endif

                            <details>
                                <summary class="btn btn-ghost btn-sm" style="list-style:none;cursor:pointer;">Modifier</summary>
                                <form method="POST" action="{{ route('admin.users.update', $user) }}" style="display:grid;gap:.5rem;min-width:320px;margin-top:.5rem;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" value="{{ $user->name }}" placeholder="Nom" required>
                                    <input type="text" name="prenom" value="{{ $user->prenom }}" placeholder="Prenom">
                                    <input type="email" name="email" value="{{ $user->email }}" placeholder="Email" required>
                                    <input type="text" name="cin" value="{{ $user->cin }}" maxlength="8" minlength="8" pattern="[01]\d{7}" inputmode="numeric" title="Le CIN doit contenir exactement 8 chiffres et commencer par 0 ou 1" placeholder="Ex: 01234567" required>
                                    <input type="text" name="matricule" value="{{ $user->matricule }}" placeholder="Matricule">
                                    <select name="role">
                                        <option value="">Sans role</option>
                                        @foreach($roles as $roleValue => $roleLabel)
                                            <option value="{{ $roleValue }}" @selected($user->role === $roleValue)>{{ $roleLabel }}</option>
                                        @endforeach
                                    </select>
                                    <label style="display:flex;gap:.45rem;align-items:center;font-size:.78rem;">
                                        <input type="checkbox" name="is_admin_approved" value="1" @checked($user->is_admin_approved)>
                                        <span>Compte approuve</span>
                                    </label>
                                    <button type="submit" class="btn btn-sm">Enregistrer</button>
                                </form>
                            </details>

                            <button type="button" onclick="openGlobalDeleteModal('{{ route('admin.users.destroy', $user->id) }}', '{{ $user->prenom }} {{ $user->nom }}', 'Supprimer l\'utilisateur')"
                                    class="btn btn-danger btn-sm text-danger">
                                Supprimer
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div style="text-align:center;padding:2rem;color:var(--muted);">
                            <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:.75rem;opacity:.4;"></i>
                            <div>Aucun utilisateur trouve</div>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $users->appends(request()->query())->links() }}
    </div>
</div>

@endsection




