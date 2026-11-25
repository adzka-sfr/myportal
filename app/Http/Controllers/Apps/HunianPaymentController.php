<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\HunianLog;
use App\Models\HunianPayment;
use App\Models\HunianRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/** Verifikasi pembayaran: cocokkan bukti dengan catatan sebelum dihitung sebagai pemasukan. */
class HunianPaymentController extends Controller
{
    public const STATUSES = ['pending' => 'Menunggu verifikasi', 'matched' => 'Sudah match'];

    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:all,'.implode(',', array_keys(self::STATUSES))]]);
        $status = $filters['status'] ?? 'pending';

        $payments = HunianPayment::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("FIELD(status, 'pending', 'matched')")
            ->orderBy($status === 'matched' ? 'matched_at' : 'id', $status === 'pending' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $pending = HunianPayment::query()->where('status', 'pending');

        return view('portal.kelola_hunian.payments', [
            'payments' => $payments,
            'status' => $status,
            'statuses' => self::STATUSES,
            'summary' => [
                'pending_count' => (clone $pending)->count(),
                'pending_amount' => (int) (clone $pending)->sum('amount'),
                'month_income' => (int) HunianPayment::query()->where('status', 'matched')
                    ->whereBetween('matched_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
                'total_income' => (int) HunianPayment::query()->where('status', 'matched')->sum('amount'),
            ],
        ]);
    }

    public function match(Request $request, HunianPayment $payment): RedirectResponse
    {
        if ($payment->status === 'matched') {
            return back()->withErrors(['payment' => 'Pembayaran ini sudah di-match.']);
        }

        $data = $request->validate(['amount' => ['required', 'integer', 'min:0', 'max:999999999999']], [], ['amount' => 'nominal']);
        $oldAmount = $payment->amount;

        DB::transaction(function () use ($payment, $data, $oldAmount) {
            $payment->update([
                'amount' => $data['amount'],
                'status' => 'matched',
                'matched_at' => now(),
                'matched_by_name' => auth()->user()?->name,
            ]);
            $note = (int) $data['amount'] !== $oldAmount
                ? ' Nominal dikoreksi dari Rp '.number_format($oldAmount, 0, ',', '.').'.'
                : '';
            HunianLog::record('payment_matched', "Pembayaran {$payment->periodLabel()} cocok dengan bukti, dicatat sebagai pemasukan.{$note}", HunianRoom::find($payment->room_id), $payment->occupant_name, (int) $data['amount']);
        });

        return back()->with('status', 'Pembayaran di-match dan dicatat sebagai pemasukan.');
    }

    public function destroyProof(HunianPayment $payment): RedirectResponse
    {
        if ($payment->status !== 'matched' || ! $payment->proof_path) {
            return back()->withErrors(['payment' => 'Gambar hanya dapat dihapus setelah pembayaran di-match.']);
        }

        DB::transaction(function () use ($payment) {
            Storage::disk('local')->delete($payment->proof_path);
            $payment->update(['proof_path' => null, 'proof_deleted_at' => now()]);
            HunianLog::record('proof_deleted', "Gambar bukti pembayaran {$payment->periodLabel()} dihapus.", HunianRoom::find($payment->room_id), $payment->occupant_name, $payment->amount);
        });

        return back()->with('status', 'Gambar bukti dihapus.');
    }

    public function proof(HunianPayment $payment): Response
    {
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
