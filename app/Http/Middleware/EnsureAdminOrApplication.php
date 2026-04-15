<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrApplication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->isAdminOrApplication()) {
            abort(403, 'Admin or Applications access required.');
        }

        return $next($request);
    }
}
