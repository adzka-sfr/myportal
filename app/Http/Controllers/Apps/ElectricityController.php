<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ElectricityController extends Controller
{
    public function index(): View
    {
        return view('apps.electricity');
    }

    public function calculate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'watts' => ['required', 'numeric', 'min:0.1', 'max:1000000'],
            'hours_per_day' => ['required', 'numeric', 'min:0', 'max:24'],
            'days_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'tariff' => ['required', 'numeric', 'min:0', 'max:10000000'],
        ]);

        $monthlyKwh = ($data['watts'] / 1000) * $data['hours_per_day'] * $data['days_per_month'];

        return back()->withInput()->with('result', [
            'energy' => $monthlyKwh,
            'cost' => $monthlyKwh * $data['tariff'],
        ]);
    }

    public function breakdown(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tariff' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'appliances' => ['required', 'array', 'min:1', 'max:30'],
            'appliances.*.name' => ['required', 'string', 'max:80'],
            'appliances.*.watts' => ['required', 'numeric', 'min:0.1', 'max:1000000'],
            'appliances.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'appliances.*.days' => ['required', 'integer', 'min:1', 'max:31'],
        ]);

        $items = collect($data['appliances'])->map(function (array $item) use ($data) {
            $kwh = ($item['watts'] / 1000) * $item['hours'] * $item['days'];

            return ['name' => $item['name'], 'kwh' => $kwh, 'cost' => $kwh * $data['tariff']];
        });

        return back()->withInput()->with('breakdown', [
            'items' => $items->all(),
            'energy' => $items->sum('kwh'),
            'cost' => $items->sum('cost'),
        ]);
    }
}
