<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function updatePhoto(Request $request, AuditService $auditService): RedirectResponse
    {
        $data = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        $user = $request->user();
        $oldPath = $user->profile_photo ?: $user->profile_image_path;

        $path = $data['profile_photo']->store('profile_photos', 'public');

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        $user->forceFill([
            'profile_photo' => $path,
            'profile_image_path' => $path,
        ])->save();

        $auditService->log($user->id, 'profile_photo_updated', $user, [
            'profile_photo' => $path,
        ], $request);

        return back()->with('success', 'Photo de profil mise a jour avec succes.');
    }

    public function deletePhoto(Request $request, AuditService $auditService): RedirectResponse
    {
        $user = $request->user();
        $path = $user->profile_photo ?: $user->profile_image_path;

        if (! $path) {
            return back()->with('status', 'Aucune photo de profil a supprimer.');
        }

        Storage::disk('public')->delete($path);

        $user->forceFill([
            'profile_photo' => null,
            'profile_image_path' => null,
        ])->save();

        $auditService->log($user->id, 'profile_photo_deleted', $user, [], $request);

        return back()->with('success', 'Photo de profil supprimee avec succes.');
    }
}

