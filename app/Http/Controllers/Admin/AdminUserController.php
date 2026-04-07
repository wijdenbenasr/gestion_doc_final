<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\VerificationCodeService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing users in the admin panel.
 */
class AdminUserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $query = User::query()->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cin', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($approval = $request->query('approval')) {
            if ($approval === 'approved') {
                $query->where('is_admin_approved', true);
            }

            if ($approval === 'pending') {
                $query->where('is_admin_approved', false);
            }
        }

        $users = $query->paginate(20)->withQueryString();
        $stats = array(
            'total' => User::count(),
            'pending_approval' => User::where('is_admin_approved', false)->count(),
            'awaiting_email_verification' => User::where('is_admin_approved', true)
                ->whereNull('email_verified_at')
                ->count(),
            'admins' => User::where('role', 'admin')->count(),
            'creators' => User::where('role', 'creator')->count(),
            'reviewers' => User::whereIn('role', array('validator', 'approver'))->count(),
        );

        return view('admin.users.index', array(
            'users' => $users,
            'stats' => $stats,
            'filters' => array(
                'search' => $request->query('search', ''),
                'role' => $request->query('role', ''),
                'approval' => $request->query('approval', ''),
            ),
        ));
    }

    /**
     * Store a newly created user.
     */
    public function store(
        Request $request,
        AuditService $audit_service,
        VerificationCodeService $verification_code_service
    ): RedirectResponse {
        $data = $request->validate(array(
            'name' => array('required', 'string', 'max:255'),
            'prenom' => array('nullable', 'string', 'max:255'),
            'email' => array('required', 'email', 'max:255', 'unique:users,email'),
            'cin' => array('nullable', 'string', 'max:255', 'unique:users,cin'),
            'matricule' => array('nullable', 'string', 'max:255', 'unique:users,matricule'),
            'password' => array('required', 'string', 'min:8', 'confirmed'),
            'role' => array('required', 'in:creator,validator,approver,admin'),
            'is_admin_approved' => array('nullable', 'boolean'),
        ));

        $is_approved = (bool) ($data['is_admin_approved'] ?? false);

        $user = User::create(array(
            'name' => $data['name'],
            'prenom' => $data['prenom'] ?? null,
            'email' => $data['email'],
            'cin' => $data['cin'] ?? null,
            'matricule' => $data['matricule'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'],
            'is_admin_approved' => $is_approved,
            'admin_approved_at' => $is_approved ? now() : null,
        ));

        if ($user->is_admin_approved && ! $user->hasVerifiedEmail()) {
            $verification_code_service->sendForUser($user);
        }

        $audit_service->log(
            $request->user()->id,
            'user_created',
            $user,
            array(
                'role' => $user->role,
                'is_admin_approved' => $user->is_admin_approved,
            ),
            $request
        );

        return back()->with('status', 'Utilisateur cree avec succes.');
    }

    /**
     * Update the specified user.
     */
    public function update(
        Request $request,
        User $user,
        AuditService $audit_service,
        VerificationCodeService $verification_code_service
    ): RedirectResponse {
        $data = $request->validate(array(
            'name' => array('required', 'string', 'max:255'),
            'prenom' => array('nullable', 'string', 'max:255'),
            'email' => array('required', 'email', 'max:255', 'unique:users,email,' . $user->id),
            'cin' => array('nullable', 'string', 'max:255', 'unique:users,cin,' . $user->id),
            'matricule' => array('nullable', 'string', 'max:255', 'unique:users,matricule,' . $user->id),
            'role' => array('nullable', 'in:creator,validator,approver,admin'),
            'is_admin_approved' => array('nullable', 'boolean'),
        ));

        $was_approved = (bool) $user->is_admin_approved;

        if ($request->user()->is($user) && ($data['role'] ?? $user->role) !== 'admin') {
            return back()->withErrors(array('role' => 'Vous ne pouvez pas retirer votre propre role administrateur.'));
        }

        $user->forceFill(array(
            'name' => $data['name'],
            'prenom' => $data['prenom'] ?? null,
            'email' => $data['email'],
            'cin' => $data['cin'] ?? null,
            'matricule' => $data['matricule'] ?? null,
            'role' => $data['role'] ?? null,
            'is_admin_approved' => (bool) ($data['is_admin_approved'] ?? false),
            'admin_approved_at' => (bool) ($data['is_admin_approved'] ?? false) ? ($user->admin_approved_at ?? now()) : null,
        ))->save();

        if (! $was_approved && $user->is_admin_approved && ! $user->hasVerifiedEmail()) {
            $verification_code_service->sendForUser($user);
        }

        $audit_service->log(
            $request->user()->id,
            'user_updated',
            $user,
            array(
                'role' => $user->role,
                'is_admin_approved' => $user->is_admin_approved,
            ),
            $request
        );

        return back()->with('status', 'Utilisateur mis a jour.');
    }

    /**
     * Resend verification code for the user.
     */
    public function resendVerificationCode(
        User $user,
        Request $request,
        AuditService $audit_service,
        VerificationCodeService $verification_code_service
    ): RedirectResponse {
        if (! $user->is_admin_approved) {
            return back()->withErrors(array(
                'verification' => 'Le compte doit etre approuve avant de renvoyer un code.',
            ));
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Cet utilisateur a deja verifie son email.');
        }

        $verification_code_service->sendForUser($user);

        $audit_service->log(
            $request->user()->id,
            'verification_code_resent',
            $user,
            array('email' => $user->email),
            $request
        );

        return back()->with('status', 'Un nouveau code de verification a ete envoye.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user, Request $request, AuditService $audit_service): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(array('delete' => 'Vous ne pouvez pas supprimer votre propre compte administrateur.'));
        }

        if ($user->createdDocuments()->exists()) {
            return back()->withErrors(array('delete' => 'Impossible de supprimer un utilisateur qui a deja cree des documents.'));
        }

        try {
            $audit_service->log($request->user()->id, 'user_deleted', $user, array(), $request);
            $user->delete();
        } catch (QueryException) {
            return back()->withErrors(array('delete' => 'Suppression impossible car cet utilisateur est encore reference dans le systeme.'));
        }

        return back()->with('status', 'Utilisateur supprime.');
    }
}
