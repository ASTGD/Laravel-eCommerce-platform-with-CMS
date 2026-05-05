<?php

namespace Platform\PlatformSupport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class ForceCurrentRootUrl
{
    public function handle(Request $request, Closure $next)
    {
        $rootUrl = $request->getSchemeAndHttpHost();

        config([
            'app.url' => $rootUrl,
            'filesystems.disks.public.url' => $rootUrl.'/storage',
        ]);

        URL::forceRootUrl($rootUrl);
        URL::forceScheme($request->getScheme());

        return $next($request);
    }
}
