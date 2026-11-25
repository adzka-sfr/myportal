@extends('layouts.app', ['title' => 'Pembayaran · Penyewa Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Keuangan</div>
        <h1>Riwayat pembayaran</h1>
        <p class="muted">Setiap pembayaran dicek pengelola. Status "Terverifikasi" berarti sudah dicocokkan dengan bukti.</p>
    </div>
    <span class="app-icon app-icon-penyewa large-icon">☺</span>
</div>

@include('portal.penyewa_hunian._nav')

@if($occupancy)
    @php($days = $occupancy->daysUntilDue())
    <section class="hn-stats">
        <div @class(['hn-stat', 'is-alert' => $days !== null && $days < 0])><span>Tenggat berikutnya</span><strong>{{ $occupancy->paid_until?->format('d M Y') ?? '—' }}</strong><small>@if($days === null)Belum ada tanggal @elseif($days < 0)Terlambat {{ abs($days) }} hari @elseif($days === 0)Hari ini @else{{ $days }} hari lagi @endif</small></div>
        <div class="hn-stat"><span>Total terverifikasi</span><strong>Rp {{ number_format($verifiedTotal, 0, ',', '.') }}</strong><small>seluruh masa tinggal</small></div>
        <div class="hn-stat"><span>Menunggu verifikasi</span><strong>{{ $pendingCount }}</strong><small>pembayaran</small></div>
    </section>
@endif

<section class="hn-panel">
    @if($payments->isEmpty())
        <div class="empty-state compact"><h3>Belum ada pembayaran</h3><p>Pembayaran yang dicatat pengelola akan tampil di sini.</p></div>
    @else
        <div class="table-wrap">
            <table class="hn-table">
                <thead><tr><th>Dicatat</th><th>Kamar</th><th>Periode</th><th>Nominal</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td style="white-space:nowrap">{{ $payment->created_at->format('d M Y') }}</td>
                            <td>{{ $payment->room_number ?? '—' }}</td>
                            <td>{{ $payment->periodLabel() }}<br><small class="hn-empty-text">{{ $payment->period_start->format('d M Y') }} → {{ $payment->period_end->format('d M Y') }}</small></td>
                            <td style="white-space:nowrap">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>
                                @if($payment->status === 'matched')
                                    <span class="hn-act act-payment_matched">Terverifikasi</span><br><small class="hn-empty-text">{{ $payment->matched_at?->format('d M Y') }}</small>
                                @else
                                    <span class="hn-act">Menunggu verifikasi</span>
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
@endsection
