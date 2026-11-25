@extends('layouts.app', ['title' => 'Penyewa Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Aplikasi penghuni</div>
        <h1>Halo, {{ Str::before($tenant->full_name, ' ') }}.</h1>
        <p class="muted">Lihat kamarmu, tenggat pembayaran, dan sampaikan keluhan ke pengelola.</p>
    </div>
    <span class="app-icon app-icon-penyewa large-icon">☺</span>
</div>

@include('portal.penyewa_hunian._nav')

@if($occupancy)
    @php
        $days = $occupancy->daysUntilDue();
        $state = $occupancy->paymentState();
    @endphp

    @if($state === 'overdue')
        <div class="hn-alert overdue">Pembayaran sewa terlambat {{ abs($days) }} hari. Segera hubungi pengelola untuk melakukan pembayaran.</div>
    @elseif($state === 'soon')
        <div class="hn-alert soon">{{ $days === 0 ? 'Sewa jatuh tempo hari ini.' : "Sewa jatuh tempo {$days} hari lagi." }} Siapkan pembayaranmu.</div>
    @endif

    <section class="hn-stats" aria-label="Ringkasan">
        <div class="hn-stat"><span>Kamar yang dihuni</span><strong>{{ $occupancy->room->room_number }}</strong><small>Lantai {{ $occupancy->room->floor }}@if($occupancy->room->room_type) · {{ $occupancy->room->room_type }}@endif</small></div>
        <div @class(['hn-stat', 'is-alert' => $state === 'overdue'])><span>Tenggat pembayaran berikutnya</span><strong>{{ $occupancy->paid_until?->format('d M Y') ?? '—' }}</strong><small>@if($days === null)Belum ada tanggal terbayar @elseif($days < 0)Terlambat {{ abs($days) }} hari @elseif($days === 0)Hari ini @else{{ $days }} hari lagi @endif</small></div>
        <div class="hn-stat"><span>Sewa per bulan</span><strong>Rp {{ number_format($occupancy->rent_price, 0, ',', '.') }}</strong><small>sesuai kesepakatan</small></div>
        <div class="hn-stat"><span>Keluhan aktif</span><strong>{{ $openTickets }}</strong><small>@if($unreadTickets > 0){{ $unreadTickets }} balasan baru @else belum selesai @endif</small></div>
    </section>

    <div class="hn-split">
        <section class="hn-panel">
            <h2>Detail kamar</h2>
            <p>Informasi hunian aktifmu.</p>
            <dl class="hn-kv">
                <dt>Nomor kamar</dt><dd>{{ $occupancy->room->room_number }}</dd>
                <dt>Lantai</dt><dd>{{ $occupancy->room->floor }}</dd>
                <dt>Tipe</dt><dd>{{ $occupancy->room->room_type ?? '—' }}</dd>
                <dt>Mulai menghuni</dt><dd>{{ $occupancy->check_in_date?->format('d M Y') ?? '—' }}@if($occupancy->check_in_date) <span class="hn-empty-text">({{ (int) $occupancy->check_in_date->startOfDay()->diffInDays(now()->startOfDay()) }} hari)</span>@endif</dd>
                <dt>Fasilitas</dt>
                <dd>
                    @if($occupancy->room->facilities->isEmpty())—@else
                        <div class="hn-tags">@foreach($occupancy->room->facilities as $facility)<span>{{ $facility->name }}</span>@endforeach</div>
                    @endif
                </dd>
                @if($occupancy->notes)<dt>Catatan</dt><dd>{{ $occupancy->notes }}</dd>@endif
            </dl>
        </section>

        <aside class="hn-panel">
            <h2>Pembayaran terakhir</h2>
            <p>@if($pendingPayments > 0){{ $pendingPayments }} menunggu verifikasi pengelola.@else Semua pembayaran sudah terverifikasi.@endif</p>
            @if($recentPayments->isEmpty())
                <span class="hn-empty-text">Belum ada pembayaran tercatat.</span>
            @else
                <ul class="hn-feed">
                    @foreach($recentPayments as $payment)
                        <li>
                            <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong> · {{ $payment->periodLabel() }}
                            <small>{{ $payment->period_start->format('d M Y') }} → {{ $payment->period_end->format('d M Y') }} · {{ $payment->status === 'matched' ? 'Terverifikasi' : 'Menunggu verifikasi' }}</small>
                        </li>
                    @endforeach
                </ul>
            @endif
            <a class="button button-quiet button-small" style="margin-top:14px" href="{{ route('apps.penyewa.payments') }}">Riwayat lengkap</a>
        </aside>
    </div>
@elseif($reservation)
    <div class="empty-state">
        <div class="empty-icon">⌂</div>
        <h3>Kamar {{ $reservation->room?->room_number }} sudah dipesan untukmu</h3>
        <p>@if($reservation->check_in_date)Rencana masuk {{ $reservation->check_in_date->format('d M Y') }}.@else Pengelola akan menghubungimu untuk jadwal masuk.@endif Info tenggat pembayaran tampil setelah check-in.</p>
    </div>
@else
    <div class="empty-state">
        <div class="empty-icon">⌂</div>
        <h3>Belum ada kamar yang kamu huni</h3>
        <p>Data kamar akan tampil setelah pengelola melakukan check-in untukmu.</p>
    </div>
@endif
@endsection
