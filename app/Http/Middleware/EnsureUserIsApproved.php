<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Les admins sont toujours approve (ou null pour le Super Admin)
        if ($user && $user->role === 'admin') {
            return $next($request);
        }

        // Les autres roles ont besoin de is_admin_approved
        if ($user && !$user->is_admin_approved) {
            abort(403, 'Votre compte n\'a pas encore ete approuve par un administrateur.');
        }

        return $next($request);
    }
}
