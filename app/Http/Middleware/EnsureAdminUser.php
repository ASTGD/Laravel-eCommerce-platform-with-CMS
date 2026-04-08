<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PlatformSupport\Enums\UserRole;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $role = $user->role instanceof UserRole ? $user->role : UserRole::tryFrom((string) $user->role);

        abort_unless($role instanceof UserRole, 403);

        return $next($request);
    }
}
