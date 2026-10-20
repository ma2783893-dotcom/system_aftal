<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rules for employee accounts only (admins/supervisors are not affected):
 *  1. Desktop/laptop browsers are blocked — employees may use the system from a phone only.
 *  2. If the admin created the account with a temporary password, the employee
 *     is forced to the change-password page before anything else.
 */
class EmployeeAccessGuard
{
    /** Routes that must stay reachable so the user is never locked in a loop. */
    private const ALWAYS_ALLOWED = ['login', 'logout', 'lang/*', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'employee' || $request->is(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        if (!self::isMobile($request->userAgent())) {
            return response()->view('errors.mobile-only', [], 403);
        }

        if ($user->must_change_password && !$request->is('change-password')) {
            return redirect('/change-password');
        }

        return $next($request);
    }

    public static function isMobile(?string $userAgent): bool
    {
        return (bool) preg_match('/Android|iPhone|iPod|iPad|Mobile|Windows Phone|webOS|BlackBerry/i', (string) $userAgent);
    }
}
