<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('account.profile', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfileImage(Request $request, AuditService $auditService): RedirectResponse
    {
        $data = $request->validate([
            'profile_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $oldPath = $user->profile_image_path;
        $path = $data['profile_image']->store('profile-images', 'public');

        $user->forceFill([
            'profile_image_path' => $path,
        ])->save();

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        $auditService->log($user->id, 'profile_image_updated', $user, [
            'profile_image_path' => $path,
        ], $request);

        return back()->with('status', 'Image de profil mise a jour avec succes.');
    }

    public function destroyProfileImage(Request $request, AuditService $auditService): RedirectResponse
    {
        $user = $request->user();

        if (! $user->profile_image_path) {
            return back()->with('status', 'Aucune image de profil a supprimer.');
        }

        Storage::disk('public')->delete($user->profile_image_path);

        $user->forceFill([
            'profile_image_path' => null,
        ])->save();

        $auditService->log($user->id, 'profile_image_deleted', $user, [], $request);

        return back()->with('status', 'Image de profil supprimee avec succes.');
    }
}
