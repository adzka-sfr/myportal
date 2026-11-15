<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationAccess
{
    public function handle(Request $request, Closure $next, string $application): Response
    {
        abort_unless(isset(config('portal.applications')[$application]), 404);
        abort_unless($request->user()?->canAccessApplication($application), 403, 'Kamu belum mendapat akses ke aplikasi ini.');

        return $next($request);
    }
}
