<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    public function index(): View
    {
        return view('apps.discount');
    }

    public function calculate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $saved = $data['price'] * $data['discount'] / 100;

        return back()->withInput()->with('result', [
            'final' => $data['price'] - $saved,
            'saved' => $saved,
            'effective' => $data['discount'],
        ]);
    }

    public function stacked(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'second_discount' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $final = $data['price'] * (1 - $data['discount'] / 100) * (1 - $data['second_discount'] / 100);
        $saved = $data['price'] - $final;

        return back()->withInput()->with('stacked_result', [
            'final' => $final,
            'saved' => $saved,
            'effective' => $data['price'] > 0 ? $saved / $data['price'] * 100 : 0,
        ]);
    }
}
