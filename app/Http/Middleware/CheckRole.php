<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Split roles by pipe if multiple allowed: 'admin|manager'
        $roles = explode('|', $role);

        if (! $request->user() || ! $request->user()->role || ! in_array($request->user()->role->slug, $roles)) {
            return response()->json(['message' => 'Accès refusé. Rôle requis : ' . $role], 403);
        }

        return $next($request);
    }
}
