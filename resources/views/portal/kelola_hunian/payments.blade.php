@extends('layouts.app', ['title' => 'Verifikasi bayar · Kelola Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Keuangan</div>
        <h1>Verifikasi pembayaran</h1>
        <p class="muted">Cocokkan gambar bukti dengan catatan. Uang baru dihitung sebagai pemasukan setelah kamu menekan Match.</p>
    </div>
    <span class="app-icon app-icon-hunian large-icon">⌂</span>
</div>

@include('portal.kelola_hunian._nav')

<section class="hn-stats" aria-label="Ringkasan pembayaran">
    <div @class(['hn-stat', 'is-alert' => $summary['pending_count'] > 0])><span>Menunggu verifikasi</span><strong>{{ $summary['pending_count'] }}</strong><small>Rp {{ number_format($summary['pending_amount'], 0, ',', '.') }} belum dihitung</small></div>
    <div class="hn-stat"><span>Pemasukan bulan ini</span><strong>Rp {{ number_format($summary['month_income'], 0, ',', '.') }}</strong><small>sudah match</small></div>
    <div class="hn-stat"><span>Total pemasukan</span><strong>Rp {{ number_format($summary['total_income'], 0, ',', '.') }}</strong><small>seluruh pembayaran match</small></div>
</section>

<section class="hn-panel">
    <div class="hn-filters" style="margin-bottom:16px">
        @foreach(['pending' => 'Menunggu verifikasi', 'matched' => 'Sudah match', 'all' => 'Semua'] as $value => $label)
            <a href="{{ route('apps.hunian.payments', ['status' => $value]) }}" @class(['hn-filter', 'is-active' => $status === $value]) style="text-decoration:none">{{ $label }}</a>
        @endforeach
    </div>

    @if($payments->isEmpty())
        <div class="empty-state compact"><h3>{{ $status === 'pending' ? 'Tidak ada pembayaran yang menunggu' : 'Belum ada data' }}</h3><p>Pembayaran muncul di sini setelah dicatat dari halaman Monitoring.</p></div>
    @else
        <div class="table-wrap">
            <table class="hn-table">
                <thead><tr><th>Bukti</th><th>Penghuni / kamar</th><th>Periode</th><th>Nominal</th><th>Status</th><th class="align-right">Aksi</th></tr></thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>
                                @if($payment->proof_path)
                                    <img class="hn-thumb" loading="lazy" src="{{ route('apps.hunian.payments.proof', $payment) }}" alt="Bukti pembayaran kamar {{ $payment->room_number }}" data-proof="{{ route('apps.hunian.payments.proof', $payment) }}">
                                @elseif($payment->proof_deleted_at)
                                    <small class="hn-empty-text">Gambar dihapus<br>{{ $payment->proof_deleted_at->format('d M Y') }}</small>
                                @else
                                    <small class="hn-empty-text">Tanpa bukti</small>
                                @endif
                            </td>
                            <td><strong>{{ $payment->occupant_name }}</strong><br><small class="hn-empty-text">Kamar {{ $payment->room_number ?? '—' }} · dicatat {{ $payment->created_at->format('d M Y H:i') }}</small></td>
                            <td>{{ $payment->periodLabel() }}<br><small class="hn-empty-text">{{ $payment->period_start->format('d M Y') }} → {{ $payment->period_end->format('d M Y') }}</small></td>
                            <td style="white-space:nowrap">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>
                                @if($payment->status === 'matched')
                                    <span class="hn-act act-payment_matched">Match</span><br><small class="hn-empty-text">{{ $payment->matched_at?->format('d M Y H:i') }}@if($payment->matched_by_name) · {{ $payment->matched_by_name }}@endif</small>
                                @else
                                    <span class="hn-act">Menunggu</span>
                                @endif
                            </td>
                            <td class="align-right">
                                @if($payment->status === 'pending')
                                    <form method="POST" action="{{ route('apps.hunian.payments.match', $payment) }}" class="hn-match-form" onsubmit="return confirm('Bukti sudah sesuai dengan catatan? Nominal Rp ' + this.amount.value + ' akan dihitung sebagai pemasukan.')">
                                        @csrf @method('PATCH')
                                        <input type="number" name="amount" min="0" step="500" value="{{ $payment->amount }}" required aria-label="Nominal sesuai bukti">
                                        <button class="button button-primary button-small" type="submit">Match</button>
                                    </form>
                                @elseif($payment->proof_path)
                                    <form method="POST" action="{{ route('apps.hunian.payments.proof.destroy', $payment) }}" onsubmit="return confirm('Hapus gambar bukti ini? Gambar tidak dapat dikembalikan.')">
                                        @csrf @method('DELETE')
                                        <button class="text-button danger-text" type="submit">Hapus gambar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $payments->links() }}</div>
    @endif
</section>

<dialog class="hn-dialog" id="proof-dialog" style="width:min(900px,calc(100vw - 28px))">
    <div style="padding:20px;display:flex;flex-direction:column;gap:12px">
        <img class="hn-proof-img" id="proof-img" alt="Bukti pembayaran">
        <div class="hn-dialog-actions">
            <a class="button button-quiet" id="proof-open" href="#" target="_blank" rel="noopener">Buka di tab baru</a>
            <button class="button button-primary" type="button" data-close-dialog>Tutup</button>
        </div>
    </div>
</dialog>

<script>
(function () {
    var dialog = document.getElementById('proof-dialog');
    var img = document.getElementById('proof-img');
    document.querySelectorAll('[data-proof]').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            img.src = thumb.dataset.proof;
            document.getElementById('proof-open').href = thumb.dataset.proof;
            dialog.showModal();
        });
    });
    dialog.querySelector('[data-close-dialog]').addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    dialog.addEventListener('close', function () { img.removeAttribute('src'); });
})();
</script>
@endsection
