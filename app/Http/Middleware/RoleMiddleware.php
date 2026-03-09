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
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if ($request->user()) {
            // Admins always have access
            if ($request->user()->role_id === 1) {
                return $next($request);
            }
            // Check if the user has the required role
            foreach ($roles as $role) {
                if ($request->user()->hasRole($role)) {
                    return $next($request);
                }
            }
        }

        return response()->json([
            'message' => 'Forbidden. You do not have the required role to access this resource.',
        ], 403);
    }
}
