<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Accès refusé. Permission requise : ' . $permission], 403);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $permissions = collect(explode('|', $permission))
            ->map(fn (string $value) => trim($value))
            ->filter();

        if (! $user->hasAnyPermissionName($permissions->all())) {
            return response()->json(['message' => 'Accès refusé. Permission requise : ' . $permission], 403);
        }

        return $next($request);
    }
}
