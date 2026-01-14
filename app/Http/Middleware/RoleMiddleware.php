<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->user() && ($request->user()->role_id === 1 || $request->user()->hasRole($role))) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden. You do not have the required role to access this resource.',
        ], 403);
    }
}
