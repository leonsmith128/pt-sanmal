<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /** Dipakai di route: ->middleware(EnsureUserHasRole::using(UserRole::Admin, UserRole::Superadmin)) */
    public static function using(UserRole ...$roles): string
    {
        return static::class.':'.implode(',', array_map(fn (UserRole $role) => $role->value, $roles));
    }

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Tamu diarahkan ke login (middleware 'auth' juga sudah mengurus ini lebih dulu).
        if (! $user) {
            return redirect()->route('login');
        }

        // #region agent log
        @file_put_contents('/home/ammar/Projects/pt-sanmal/.cursor/debug-ba0568.log', json_encode([
            'sessionId' => 'ba0568',
            'hypothesisId' => 'D',
            'location' => 'EnsureUserHasRole.php:handle',
            'message' => 'role gate',
            'data' => [
                'path' => $request->path(),
                'allowed' => $roles,
                'userRole' => $user->role->value,
                'isManager' => $user->isManager(),
                'adminComponentExists' => file_exists(resource_path('views/pages/admin/⚡attendance.blade.php')),
                'pagesAttendanceExists' => file_exists(resource_path('views/pages/⚡attendance.blade.php')),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion

        // Sudah login tapi tidak berhak: 403.
        if (! in_array($user->role->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
