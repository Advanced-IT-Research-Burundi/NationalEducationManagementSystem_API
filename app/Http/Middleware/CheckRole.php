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
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Accès refusé. Rôle requis : ' . $role], 403);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $roles = collect(explode('|', $role))
            ->map(fn (string $value) => trim($value))
            ->filter();

        $hasRequiredRole = $roles->contains(fn (string $value) => $user->matchesRoleIdentifier($value));

        if (! $hasRequiredRole) {
            return response()->json(['message' => 'Accès refusé. Rôle requis : ' . $role], 403);
        }

        return $next($request);
    }
}
