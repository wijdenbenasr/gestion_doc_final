<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiAuthController extends BaseApiController
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Identifiants incorrects', 401);
        }

        if (! $user->email_verified_at) {
            return $this->error('Email non vérifié', 403);
        }

        if (! $user->is_admin_approved) {
            return $this->error('Compte en attente de validation admin', 403);
        }

        // Mobile check: Only admin allowed
        $userAgent = $request->header('User-Agent');
        $isMobile = preg_match('/Mobile|Android|iPhone/i', $userAgent);

        if ($isMobile && $user->role !== 'admin') {
            return $this->error('Accès mobile réservé aux administrateurs', 403);
        }

        $token = Str::random(80);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => $request->device_name ?? 'api-token',
            'token' => hash('sha256', $token),
        ]);

        return $this->success([
            'user' => $user,
            'token' => $token,
        ], 'Connexion réussie');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('api_token')?->delete();

        return $this->success(null, 'Déconnexion réussie');
    }
}
