<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->user()?->hunianTenant;
        abort_unless($tenant?->is_active, 403, 'Akunmu belum terdaftar sebagai penghuni aktif.');
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
