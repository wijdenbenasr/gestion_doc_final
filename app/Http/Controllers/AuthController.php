<?php

namespace App\Http\Controllers;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\AuditService;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, AuditService $auditService): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Identifiants invalides.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (! Auth::user()->is_admin_approved) {
            Auth::logout();

            return redirect()->route('login')
                ->withErrors(['email' => 'Compte en attente de validation par l\'administrateur.']);
        }

        if (! Auth::user()->hasVerifiedEmail()) {
            Auth::logout();

            return redirect()->route('auth.verify.show', ['email' => $credentials['email']])
                ->with('status', 'Votre compte a ete approuve. Saisissez le code recu par email pour activer l\'acces.');
        }

        $auditService->log(Auth::id(), 'login', Auth::user(), [], $request);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request, AuditService $auditService): RedirectResponse
    {
        if ($request->user()) {
            $auditService->log($request->user()->id, 'logout', $request->user(), [], $request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'cin' => ['required', 'digits:8', 'regex:/^[01]\d{7}$/', 'unique:users,cin'],
            'matricule' => ['required', 'string', 'max:255', 'unique:users,matricule'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'cin.required' => 'Le champ CIN est obligatoire.',
            'cin.digits' => 'Le CIN doit contenir exactement 8 chiffres.',
            'cin.regex' => 'Le CIN doit commencer par 0 ou 1.',
            'cin.unique' => 'Ce CIN est déjà utilisé.',
            'password.required' => 'Le champ mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        User::create([
            'name' => $data['name'],
            'prenom' => $data['prenom'],
            'cin' => $data['cin'],
            'matricule' => $data['matricule'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => null,
            'is_admin_approved' => false,
        ]);

        return redirect()->route('login')
            ->with('status', 'Compte cree. Un administrateur doit d\'abord le valider avant l\'envoi du code email.');
    }

    public function showEmailVerification(Request $request): View
    {
        return view('auth.verify-email', ['email' => $request->query('email')]);
    }

    public function verifyEmailCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();
        $record = EmailVerificationCode::where('user_id', $user->id)->first();

        if (! $user->is_admin_approved) {
            return back()->withErrors(['email' => 'Votre compte doit etre approuve par un administrateur avant verification.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Votre email est deja verifie.');
        }

        if (! $record || $record->expires_at->isPast()) {
            return back()->withErrors(['code' => 'Code expire.']);
        }

        if (! password_verify($data['code'], $record->code_hash)) {
            return back()->withErrors(['code' => 'Code invalide.']);
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $record->delete();

        return redirect()->route('login')->with('status', 'Email verifie.');
    }

    public function resendVerificationCode(
        Request $request,
        VerificationCodeService $verificationCodeService
    ): RedirectResponse {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $data['email'])->firstOrFail();

        if (! $user->is_admin_approved) {
            return back()->withErrors(['email' => 'Le compte doit etre approuve par un administrateur avant l\'envoi du code.']);
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'Cet email est deja verifie.');
        }

        $verificationCodeService->sendForUser($user);

        return back()->with('status', 'Nouveau code envoye.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Le champ mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Mot de passe reinitialise.')
            : back()->withErrors(['email' => __($status)]);
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request, AuditService $auditService): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'password.required' => 'Le champ mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $request->user()->forceFill([
            'password' => $data['password'],
        ])->save();

        $auditService->log($request->user()->id, 'password_changed', $request->user(), [], $request);

        return redirect()->back()->with('success', 'Mot de passe mis à jour avec succès.');
    }
}
