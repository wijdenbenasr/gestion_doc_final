@extends('layouts.app')

@section('title', 'Gestion des utilisateurs')

@section('content')
<div class="cards-row">
    <div class="stat-card">
        <div class="stat-label">Utilisateurs</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-meta">Comptes dans la plateforme</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">En attente</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $stats['pending_approval'] }}</div>
        <div class="stat-meta">A approuver par l admin</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Codes a saisir</div>
        <div class="stat-value" style="color:#38bdf8;">{{ $stats['awaiting_email_verification'] }}</div>
        <div class="stat-meta">Comptes approuves non verifies</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Admins</div>
        <div class="stat-value" style="color:#4ade80;">{{ $stats['admins'] }}</div>
        <div class="stat-meta">Acces d administration</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Createurs</div>
        <div class="stat-value">{{ $stats['creators'] }}</div>
        <div class="stat-meta">Auteurs de documents</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Validateurs</div>
        <div class="stat-value">{{ $stats['reviewers'] }}</div>
        <div class="stat-meta">Validateurs et approbateurs</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Creation rapide</div>
            <div class="card-sub">Ajoutez un compte depuis l interface admin et activez-le immediatement si besoin.</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.users.pending') }}" class="btn btn-ghost btn-sm">Comptes en attente</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">Dashboard</a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
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
                    <option value="creator" @selected(old('role') === 'creator')>Createur</option>
                    <option value="validator" @selected(old('role') === 'validator')>Validateur</option>
                    <option value="approver" @selected(old('role') === 'approver')>Approbateur</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                </select>
            </div>
            <div class="field">
                <label for="create_cin">CIN</label>
                <input id="create_cin" type="text" name="cin" value="{{ old('cin') }}">
            </div>
            <div class="field">
                <label for="create_matricule">Matricule</label>
                <input id="create_matricule" type="text" name="matricule" value="{{ old('matricule') }}">
            </div>
            <div class="field">
                <label for="create_password">Mot de passe</label>
                <input id="create_password" type="password" name="password" required>
            </div>
            <div class="field">
                <label for="create_password_confirmation">Confirmation</label>
                <input id="create_password_confirmation" type="password" name="password_confirmation" required>
            </div>
        </div>

        <label style="display:flex;gap:.45rem;align-items:center;font-size:.78rem;margin-top:1rem;">
            <input type="checkbox" name="is_admin_approved" value="1" @checked(old('is_admin_approved'))>
            <span>Approuver ce compte a la creation</span>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Creer l utilisateur</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Gestion des utilisateurs</div>
            <div class="card-sub">Recherchez, modifiez, approuvez, renvoyez le code ou supprimez un compte.</div>
        </div>
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
                <option value="creator" @selected($filters['role'] === 'creator')>Createur</option>
                <option value="validator" @selected($filters['role'] === 'validator')>Validateur</option>
                <option value="approver" @selected($filters['role'] === 'approver')>Approbateur</option>
                <option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
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
        <button type="submit" class="btn btn-sm">Filtrer</button>
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
                                    <input type="text" name="cin" value="{{ $user->cin }}" placeholder="CIN">
                                    <input type="text" name="matricule" value="{{ $user->matricule }}" placeholder="Matricule">
                                    <select name="role">
                                        <option value="">Sans role</option>
                                        <option value="creator" @selected($user->role === 'creator')>Createur</option>
                                        <option value="validator" @selected($user->role === 'validator')>Validateur</option>
                                        <option value="approver" @selected($user->role === 'approver')>Approbateur</option>
                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                    </select>
                                    <label style="display:flex;gap:.45rem;align-items:center;font-size:.78rem;">
                                        <input type="checkbox" name="is_admin_approved" value="1" @checked($user->is_admin_approved)>
                                        <span>Compte approuve</span>
                                    </label>
                                    <button type="submit" class="btn btn-sm">Enregistrer</button>
                                </form>
                            </details>

                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--muted);padding:1.5rem;">Aucun utilisateur trouve.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $users->links() }}</div>
</div>
@endsection
