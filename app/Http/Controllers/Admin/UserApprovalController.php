<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    public function index(): View
    {
        $users = User::where('is_admin_approved', false)->paginate(20);

        return view('admin.users.pending', compact('users'));
    }

    public function approve(
        User $user,
        Request $request,
        AuditService $auditService,
        VerificationCodeService $verificationCodeService
    ): RedirectResponse {
        $data = $request->validate([
            'role' => ['required', Rule::in(User::roleKeys())],
        ]);

        $user->forceFill([
            'role' => $data['role'],
            'is_admin_approved' => true,
            'admin_approved_at' => now(),
        ])->save();

        if (! $user->hasVerifiedEmail()) {
            $verificationCodeService->sendForUser($user);
        }

        $auditService->log(
            $request->user()->id,
            'user_approved',
            $user,
            ['role_assigned' => $data['role']],
            $request
        );

        return redirect()->back()->with('status', 'Utilisateur approuve avec le role '.$data['role'].'. Un code de verification a ete envoye par email.');
    }
}
