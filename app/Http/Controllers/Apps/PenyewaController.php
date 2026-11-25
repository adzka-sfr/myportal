<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianPayment;
use App\Models\HunianTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Aplikasi penghuni: semua data dibatasi ke penghuni yang sedang login (lihat middleware `tenant`). */
class PenyewaController extends Controller
{
    public function home(Request $request): View
    {
        $tenant = $this->tenant($request);
        $occupancy = $tenant->activeOccupancy()->with('room.facilities')->first();

        return view('portal.penyewa_hunian.index', [
            'tenant' => $tenant,
            'occupancy' => $occupancy,
            'reservation' => $occupancy ? null : $tenant->reservation()->with('room')->first(),
            'recentPayments' => $this->paymentsOf($tenant)->latest('id')->limit(3)->get(),
            'pendingPayments' => $this->paymentsOf($tenant)->where('status', 'pending')->count(),
            'openTickets' => $tenant->tickets()->where('status', '!=', 'resolved')->count(),
            'unreadTickets' => $tenant->tickets()->where('tenant_unread', true)->count(),
        ]);
    }

    public function payments(Request $request): View
    {
        $tenant = $this->tenant($request);

        return view('portal.penyewa_hunian.payments', [
            'tenant' => $tenant,
            'occupancy' => $tenant->activeOccupancy()->first(),
            'payments' => $this->paymentsOf($tenant)->latest('id')->paginate(15),
            'verifiedTotal' => (int) $this->paymentsOf($tenant)->where('status', 'matched')->sum('amount'),
            'pendingCount' => $this->paymentsOf($tenant)->where('status', 'pending')->count(),
        ]);
    }

    public function profile(Request $request): View
    {
        $tenant = $this->tenant($request);

        return view('portal.penyewa_hunian.profile', [
            'tenant' => $tenant,
            'email' => $request->user()->email,
            'stays' => $tenant->occupancies()->with('room:id,room_number,floor')->orderByRaw("FIELD(status, 'active', 'reserved', 'ended')")->latest('id')->get(),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12)],
        ], [], ['current_password' => 'kata sandi saat ini', 'password' => 'kata sandi baru']);

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('apps.penyewa.profile')->with('status', 'Kata sandi diperbarui.');
    }

    private function tenant(Request $request): HunianTenant
    {
        return $request->attributes->get('tenant');
    }

    /** Pembayaran dari seluruh masa tinggal milik penghuni ini. */
    private function paymentsOf(HunianTenant $tenant): Builder
    {
        return HunianPayment::query()->whereIn('occupancy_id', $tenant->occupancies()->select('id'));
    }
}
