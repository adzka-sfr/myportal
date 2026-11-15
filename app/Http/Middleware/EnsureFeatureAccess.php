<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureAccess
{
    public function handle(Request $request, Closure $next, string $application, string $feature): Response
    {
        $features = config("portal.applications.{$application}.features", []);
        abort_unless(isset($features[$feature]), 404);
        abort_unless($request->user()?->canUseFeature($application, $feature), 403, 'Fitur ini belum diberikan ke akunmu.');

        return $next($request);
    }
}
