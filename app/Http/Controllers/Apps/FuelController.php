<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FuelController extends Controller
{
    public function index(): View
    {
        return view('apps.fuel');
    }

    public function calculate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'distance' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'efficiency' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'fuel_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
        ]);
        $liters = $data['distance'] / $data['efficiency'];

        return back()->withInput()->with('result', [
            'liters' => $liters,
            'cost' => $liters * $data['fuel_price'],
        ]);
    }

    public function compare(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'distance' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'fuel_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'vehicle_a' => ['required', 'string', 'max:80'],
            'efficiency_a' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'vehicle_b' => ['required', 'string', 'max:80'],
            'efficiency_b' => ['required', 'numeric', 'gt:0', 'max:1000'],
        ]);
        $a = $data['distance'] / $data['efficiency_a'] * $data['fuel_price'];
        $b = $data['distance'] / $data['efficiency_b'] * $data['fuel_price'];

        return back()->withInput()->with('comparison', [
            'a' => ['name' => $data['vehicle_a'], 'cost' => $a],
            'b' => ['name' => $data['vehicle_b'], 'cost' => $b],
            'savings' => abs($a - $b),
            'cheaper' => $a <= $b ? $data['vehicle_a'] : $data['vehicle_b'],
        ]);
    }
}
