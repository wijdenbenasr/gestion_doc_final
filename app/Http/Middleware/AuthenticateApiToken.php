<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $header = $request->bearerToken();

        if (! $header) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token API manquant',
                'errors' => null,
            ], 401);
        }

        $hashedToken = hash('sha256', $header);

        $apiToken = ApiToken::with('user')
            ->where('token', $hashedToken)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $apiToken || ! $apiToken->user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token API invalide',
                'errors' => null,
            ], 401);
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($apiToken->user);
        $request->setUserResolver(fn () => $apiToken->user);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
