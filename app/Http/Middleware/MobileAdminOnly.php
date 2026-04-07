<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->userAgent();
        $isMobile = false;

        if ($userAgent) {
            $isMobile = preg_match('/Android|iPhone|iPad|Mobile/i', $userAgent) === 1;
        }

        if ($isMobile && $request->is('login', 'register', 'email/*', 'password/*')) {
            return $next($request);
        }

        if ($isMobile && (! $request->user() || $request->user()->role !== 'admin')) {
            abort(403);
        }

        return $next($request);
    }
}
