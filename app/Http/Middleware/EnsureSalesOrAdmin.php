<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSalesOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !($user->isSalesAgent() || $user->isAdmin())) {
            abort(403, 'Sales or admin access required.');
        }

        return $next($request);
    }
}
