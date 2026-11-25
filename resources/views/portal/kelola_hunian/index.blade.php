@extends('layouts.app', ['title' => 'Kelola Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Monitoring unit</div>
        <h1>Kelola Hunian</h1>
        <p class="muted">Tiap kotak mewakili satu kamar dengan kondisi aktualnya. Pergantian penghuni, pembayaran, dan perbaikan dicatat di sini.</p>
    </div>
    <span class="app-icon app-icon-hunian large-icon">⌂</span>
</div>

@include('portal.kelola_hunian._nav')

<section class="hn-stats" aria-label="Ringkasan hunian">
    <div class="hn-stat"><span>Okupansi</span><strong>{{ $stats['occupancy'] }}%</strong><small>{{ $stats['occupied'] }} dari {{ $stats['total'] }} kamar</small><div class="hn-meter"><i style="width: {{ $stats['occupancy'] }}%"></i></div></div>
    <div class="hn-stat"><span>Kosong</span><strong>{{ $stats['available'] }}</strong><small>siap ditempati</small></div>
    <div class="hn-stat"><span>Dipesan / Perbaikan</span><strong>{{ $stats['reserved'] }} / {{ $stats['maintenance'] }}</strong><small>booking / maintenance</small></div>
    <div @class(['hn-stat', 'is-alert' => $stats['overdue'] > 0])><span>Sewa terlambat</span><strong>{{ $stats['overdue'] }}</strong><small>kamar lewat jatuh tempo</small></div>
    <div class="hn-stat"><span>Pemasukan bulan ini</span><strong>Rp {{ number_format($stats['income'], 0, ',', '.') }}</strong><small><a href="{{ route('apps.hunian.payments') }}">{{ $stats['pending_count'] }} pembayaran (Rp {{ number_format($stats['pending_amount'], 0, ',', '.') }}) menunggu verifikasi</a></small></div>
</section>

<div class="hn-toolbar">
            <div class="hn-filters" role="group" aria-label="Filter kamar">
                <button type="button" class="hn-filter is-active" data-filter="all">Semua</button>
                @foreach($statuses as $value => $label)
                    <button type="button" class="hn-filter" data-filter="{{ $value }}">{{ $label }}</button>
                @endforeach
                <button type="button" class="hn-filter" data-filter="overdue">Terlambat bayar</button>
            </div>
        </div>

        @forelse($floors as $floor => $rooms)
            <section class="hn-floor" data-floor-section>
                <div class="hn-floor-head">
                    <h2>Lantai {{ $floor }}<small>{{ $rooms->count() }} kamar · {{ $rooms->filter(fn ($r) => $r->activeOccupancy)->count() }} terisi</small></h2>
                </div>
                <div class="hn-grid">
                    @foreach($rooms as $room)
                        @php
                            $state = $room->currentStatus();
                            $occ = $room->activeOccupancy ?? $room->reservation;
                            $pay = $room->activeOccupancy?->paymentState();
                            $days = $room->activeOccupancy?->daysUntilDue();
                            $occPayload = $occ ? [
                                'id' => $occ->id,
                                'status' => $occ->status,
                                'room_number' => $room->room_number,
                                'tenant_id' => $occ->tenant_id,
                                'occupant_name' => $occ->occupant_name,
                                'occupant_phone' => $occ->occupant_phone,
                                'check_in_date' => $occ->check_in_date?->format('Y-m-d'),
                                'paid_until' => $occ->paid_until?->format('Y-m-d'),
                                'rent_price' => $occ->rent_price,
                                'notes' => $occ->notes,
                                'paid_until_label' => $occ->paid_until?->format('d M Y'),
                            ] : null;
                        @endphp
                        <article @class(['hn-room', "st-{$state}", 'pay-overdue' => $pay === 'overdue']) data-room data-status="{{ $state }}" data-paystate="{{ $pay }}">
                            <div class="hn-room-head">
                                <div>
                                    <span class="hn-number">{{ $room->room_number }}</span>
                                    @if($room->room_type)<span class="hn-type">{{ $room->room_type }}</span>@endif
                                </div>
                                <span class="hn-badge">{{ $statuses[$state] }}</span>
                            </div>

                            <div class="hn-occupant">
                                @if($occ)
                                    <strong>{{ $occ->occupant_name }}</strong>
                                    @if($occ->occupant_phone)<small>{{ $occ->occupant_phone }}</small>@endif
                                    @if($occ->check_in_date)<small>{{ $occ->status === 'reserved' ? 'Rencana masuk' : 'Masuk' }} {{ $occ->check_in_date->format('d M Y') }}</small>@endif
                                @elseif($state === 'maintenance')
                                    <strong>Sedang diperbaiki</strong>
                                    <small>{{ $room->openMaintenance->reason }} · sejak {{ $room->openMaintenance->started_at->format('d M Y') }}</small>
                                @else
                                    <span class="hn-empty-text">Belum ada penghuni.</span>
                                @endif
                            </div>

                            @if($pay)
                                <span class="hn-pay {{ $pay }}">
                                    @if($days < 0) Terlambat {{ abs($days) }} hari
                                    @elseif($days === 0) Jatuh tempo hari ini
                                    @else Terbayar sampai {{ $room->activeOccupancy->paid_until->format('d M Y') }} ({{ $days }} hari lagi)
                                    @endif
                                </span>
                            @endif

                            @if($room->facilities->isNotEmpty())
                                <div class="hn-tags">@foreach($room->facilities as $facility)<span>{{ $facility->name }}</span>@endforeach</div>
                            @endif

                            <div class="hn-price">Rp {{ number_format($occ?->rent_price ?? $room->monthly_price, 0, ',', '.') }} <small>/ bulan</small></div>

                            @if($occ?->notes)<div class="hn-note">{{ $occ->notes }}</div>@endif

                            <div class="hn-actions">
                                @switch($state)
                                    @case('available')
                                        <button type="button" class="button button-primary button-small" data-occupy="{{ json_encode(['id' => $room->id, 'room_number' => $room->room_number, 'price' => $room->monthly_price, 'mode' => 'active']) }}">Check-in</button>
                                        <button type="button" class="button button-quiet button-small" data-occupy="{{ json_encode(['id' => $room->id, 'room_number' => $room->room_number, 'price' => $room->monthly_price, 'mode' => 'reserved']) }}">Booking</button>
                                        <button type="button" class="text-button" data-maintenance="{{ json_encode(['id' => $room->id, 'room_number' => $room->room_number]) }}">Perbaikan</button>
                                        @break
                                    @case('reserved')
                                        <button type="button" class="button button-primary button-small" data-edit-occupancy="{{ json_encode($occPayload + ['activate' => true]) }}">Check-in</button>
                                        <button type="button" class="button button-quiet button-small" data-edit-occupancy="{{ json_encode($occPayload) }}">Ubah</button>
                                        <form method="POST" action="{{ route('apps.hunian.occupancies.end', $occ) }}" onsubmit="return confirm('Batalkan booking kamar {{ addslashes($room->room_number) }}?')">@csrf @method('PATCH')<button type="submit" class="text-button danger-text">Batalkan</button></form>
                                        @break
                                    @case('occupied')
                                        <button type="button" class="button button-primary button-small" data-pay="{{ json_encode(['id' => $occ->id, 'room_number' => $room->room_number, 'rent_price' => $occ->rent_price, 'paid_until_label' => $occ->paid_until?->format('d M Y'), 'paid_until' => $occ->paid_until?->format('Y-m-d'), 'check_in_date' => $occ->check_in_date?->format('Y-m-d'), 'name' => $occ->occupant_name]) }}">Catat bayar</button>
                                        <button type="button" class="button button-quiet button-small" data-edit-occupancy="{{ json_encode($occPayload) }}">Ubah</button>
                                        <form method="POST" action="{{ route('apps.hunian.occupancies.end', $occ) }}" onsubmit="return confirm('Check-out {{ addslashes($occ->occupant_name) }} dari kamar {{ addslashes($room->room_number) }}?')">@csrf @method('PATCH')<button type="submit" class="text-button danger-text">Check-out</button></form>
                                        @break
                                    @case('maintenance')
                                        <form method="POST" action="{{ route('apps.hunian.maintenance.finish', $room->openMaintenance) }}">@csrf @method('PATCH')<button type="submit" class="button button-primary button-small">Selesai perbaikan</button></form>
                                        @break
                                @endswitch
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="empty-state">
                <div class="empty-icon">⌂</div>
                <h3>Belum ada kamar</h3>
                <p>Buat kamar dan fasilitasnya terlebih dahulu di halaman Master kamar.</p>
                <a class="button button-primary" href="{{ route('apps.hunian.master') }}">Buka master kamar →</a>
            </div>
        @endforelse

        <p class="hn-empty-text" id="hn-no-match" hidden>Tidak ada kamar yang cocok dengan filter ini.</p>

@include('portal.kelola_hunian._modals')

@include('portal.kelola_hunian._select2')
@include('portal.kelola_hunian._script')
@endsection
