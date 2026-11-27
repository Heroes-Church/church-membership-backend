<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * Prevent inactive accounts from accessing
     * authenticated API endpoints.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        abort_unless(
            $user !== null,
            401,
            'Unauthenticated.'
        );

        abort_unless(
            $user->is_active,
            403,
            'This account is inactive.'
        );

        return $next($request);
    }
}
