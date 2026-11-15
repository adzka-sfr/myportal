<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        $user = Auth::user();
        $applications = collect(config('portal.applications'))
            ->map(fn (array $application, string $slug) => $application + ['slug' => $slug])
            ->filter(fn (array $application) => $user->canAccessApplication($application['slug']));

        return view('portal.dashboard', compact('applications'));
    }
}
