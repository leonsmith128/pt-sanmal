<?php

namespace App\\Http\\Middleware;

use App\\Enums\\UserRole;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

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

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless(in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
