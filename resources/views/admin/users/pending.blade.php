@extends('layouts.app')

@section('title', 'Validation des comptes')

@section('content')
<div class="cards-row">
    <div class="stat-card">
        <div class="stat-label">En attente</div>
        <div class="stat-value" style="color:#f59e0b;">{{ $users->total() }}</div>
        <div class="stat-meta">Comptes a traiter sur cette page</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Comptes en attente de validation</div>
            <div class="card-sub">Attribuez un role, approuvez le compte puis laissez l utilisateur saisir son code email.</div>
        </div>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Tous les utilisateurs</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm">Dashboard</a>
        </div>
    </div>

    @if($users->isEmpty())
        <p style="font-size:.85rem;color:var(--muted);margin-top:.5rem;">Aucun utilisateur en attente.</p>
    @else
        <div style="overflow-x:auto;margin-top:.75rem;">
            <table>
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Identifiants</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td style="font-weight:500;">{{ $user->name }} {{ $user->prenom }}</td>
                        <td>{{ $user->email }}</td>
                        <td style="font-size:.72rem;">
                            <div>CIN: {{ $user->cin ?: '-' }}</div>
                            <div>Matricule: {{ $user->matricule ?: '-' }}</div>
                        </td>
                        <td>
                            @if($user->email_verified_at)
                                <span class="badge badge-success">Verifie</span>
                            @else
                                <span class="badge badge-warning">Non verifie</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.approve', $user) }}" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
                                @csrf
                                <select name="role" required>
                                    <option value="">Choisir un role</option>
                                    <option value="creator">Createur</option>
                                    <option value="validator">Validateur</option>
                                    <option value="approver">Approbateur</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <button type="submit" class="btn btn-sm">Approuver</button>
                            </form>
                        </td>
                        <td>
                            <a href="{{ route('admin.users.index', ['search' => $user->email]) }}" class="btn btn-ghost btn-sm">Ouvrir la fiche</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $users->links() }}</div>
    @endif
</div>
@endsection
