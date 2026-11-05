<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password
            && ! $request->routeIs('account.password.first-change', 'account.password.first-change.store', 'logout')) {
            return redirect()->route('account.password.first-change');
        }

        return $next($request);
    }
}
