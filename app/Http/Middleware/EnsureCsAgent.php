<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCsAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !($user->isCsAgent() || $user->isAdmin())) {
            abort(403, 'CS agent access required.');
        }

        return $next($request);
    }
}
