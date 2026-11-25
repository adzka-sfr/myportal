@extends('layouts.app', ['title' => 'Log penghuni · Kelola Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Riwayat penghuni</div>
        <h1>Log penghuni</h1>
        <p class="muted">Seluruh penghuni dan booking, baik yang masih menghuni maupun yang sudah keluar.</p>
    </div>
    <span class="app-icon app-icon-hunian large-icon">⌂</span>
</div>

@include('portal.kelola_hunian._nav')

<section class="hn-stats" aria-label="Ringkasan penghuni">
    <div class="hn-stat"><span>Menghuni</span><strong>{{ $counts['active'] ?? 0 }}</strong><small>penghuni aktif</small></div>
    <div class="hn-stat"><span>Booking</span><strong>{{ $counts['reserved'] ?? 0 }}</strong><small>belum masuk</small></div>
    <div class="hn-stat"><span>Sudah keluar</span><strong>{{ $counts['ended'] ?? 0 }}</strong><small>riwayat</small></div>
</section>

<section class="hn-panel">
    <form method="GET" action="{{ route('apps.hunian.residents') }}" class="hn-logfilter">
        <label>Nama penghuni<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="120" placeholder="Cari nama…" style="font:inherit;padding:8px 10px;border:1px solid var(--line);border-radius:10px;background:#fff"></label>
        <label>Kamar
            <select name="room" class="js-select2" data-allow-clear="1" data-placeholder="Semua kamar"><option value=""></option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected((int) ($filters['room'] ?? 0) === $room->id)>{{ $room->room_number }}</option>@endforeach</select>
        </label>
        <label>Status
            <select name="status" class="js-select2" data-allow-clear="1" data-placeholder="Semua status"><option value=""></option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>@endforeach</select>
        </label>
        <button class="button button-quiet button-small" type="submit">Terapkan</button>
        @if(array_filter($filters))<a class="text-button" href="{{ route('apps.hunian.residents') }}">Reset</a>@endif
    </form>

    @if($residents->isEmpty())
        <div class="empty-state compact"><h3>Belum ada penghuni</h3><p>Data muncul setelah ada check-in atau booking di halaman Monitoring.</p></div>
    @else
        <div class="table-wrap">
            <table class="hn-table">
                <thead><tr><th>Penghuni</th><th>Kamar</th><th>Status</th><th>Masuk</th><th>Keluar / terbayar sampai</th><th>Lama tinggal</th><th>Sewa / bulan</th><th>Catatan</th></tr></thead>
                <tbody>
                    @foreach($residents as $resident)
                        @php
                            $stay = $resident->check_in_date && $resident->status !== 'reserved'
                                ? (int) $resident->check_in_date->startOfDay()->diffInDays(($resident->ended_at ?? now())->startOfDay())
                                : null;
                        @endphp
                        <tr>
                            <td><strong>{{ $resident->occupant_name }}</strong>@if($resident->occupant_phone)<br><small class="hn-empty-text">{{ $resident->occupant_phone }}</small>@endif</td>
                            <td>{{ $resident->room?->room_number ?? '—' }}</td>
                            <td><span class="hn-act act-{{ ['active' => 'check_in', 'reserved' => 'reserved', 'ended' => 'check_out'][$resident->status] }}">{{ $statuses[$resident->status] }}</span></td>
                            <td style="white-space:nowrap">{{ $resident->check_in_date?->format('d M Y') ?? '—' }}</td>
                            <td style="white-space:nowrap">
                                @if($resident->status === 'ended'){{ $resident->ended_at?->format('d M Y') ?? '—' }}
                                @elseif($resident->status === 'active'){{ $resident->paid_until ? 'Bayar s.d. '.$resident->paid_until->format('d M Y') : '—' }}
                                @else—@endif
                            </td>
                            <td style="white-space:nowrap">{{ $stay !== null ? $stay.' hari' : '—' }}</td>
                            <td style="white-space:nowrap">Rp {{ number_format($resident->rent_price, 0, ',', '.') }}</td>
                            <td>{{ $resident->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $residents->links() }}</div>
    @endif
</section>
@include('portal.kelola_hunian._select2')
@endsection
