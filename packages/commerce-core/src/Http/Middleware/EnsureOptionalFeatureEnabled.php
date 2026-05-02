<?php

namespace Platform\CommerceCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Platform\CommerceCore\Support\AdminFeatureToggle;
use Symfony\Component\HttpFoundation\Response;

class EnsureOptionalFeatureEnabled
{
    public function __construct(protected AdminFeatureToggle $adminFeatureToggle) {}

    public function handle(Request $request, Closure $next): Response
    {
        $feature = $this->adminFeatureToggle->featureForRouteName($request->route()?->getName());

        if ($feature && ! $this->adminFeatureToggle->enabled($feature)) {
            abort(404);
        }

        return $next($request);
    }
}
