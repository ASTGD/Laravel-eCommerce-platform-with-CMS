<?php

namespace Platform\PlatformSupport\Http\Middleware;

use Closure;

class AuthorizeAdminPermission
{
    public function handle($request, Closure $next, string $permission)
    {
        if (! bouncer()->hasPermission($permission)) {
            abort(401, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
