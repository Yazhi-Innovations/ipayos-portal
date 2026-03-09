<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SettlementAccess
{
    /**
     * Allow only users with role 'admin' or 'accountant' (role column in user table).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role ?? '', ['admin', 'accountant'], true)) {
            abort(403, 'You do not have permission to access the Settlement page.');
        }

        return $next($request);
    }
}
