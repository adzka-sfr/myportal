@extends('layouts.app', ['title' => 'Keluhan · Kelola Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Layanan penghuni</div>
        <h1>Keluhan penghuni</h1>
        <p class="muted">Keluhan yang dikirim penghuni dari aplikasi Penyewa. Balas, lampirkan foto, dan atur statusnya di sini.</p>
    </div>
    <span class="app-icon app-icon-hunian large-icon">⌂</span>
</div>

@include('portal.kelola_hunian._nav')

<section class="hn-stats" aria-label="Ringkasan keluhan">
    <div @class(['hn-stat', 'is-alert' => $unread > 0])><span>Perlu dibaca</span><strong>{{ $unread }}</strong><small>pesan baru dari penghuni</small></div>
    <div class="hn-stat"><span>Baru</span><strong>{{ $counts['open'] ?? 0 }}</strong><small>belum ditangani</small></div>
    <div class="hn-stat"><span>Diproses</span><strong>{{ $counts['in_progress'] ?? 0 }}</strong><small>sedang ditangani</small></div>
    <div class="hn-stat"><span>Selesai</span><strong>{{ $counts['resolved'] ?? 0 }}</strong><small>riwayat</small></div>
</section>

<section class="hn-panel">
    <div class="hn-filters" style="margin-bottom:16px">
        @foreach(['' => 'Semua', 'unread' => 'Perlu dibaca', 'open' => 'Baru', 'in_progress' => 'Diproses', 'resolved' => 'Selesai'] as $value => $label)
            <a href="{{ route('apps.hunian.tickets', $value ? ['status' => $value] : []) }}" @class(['hn-filter', 'is-active' => ($status ?? '') === $value]) style="text-decoration:none">{{ $label }}</a>
        @endforeach
    </div>

    @if($tickets->isEmpty())
        <div class="empty-state compact"><h3>Belum ada keluhan</h3><p>Keluhan dari penghuni akan muncul di sini.</p></div>
    @else
        <div class="table-wrap">
            <table class="hn-table">
                <thead><tr><th>Keluhan</th><th>Penghuni</th><th>Kamar</th><th>Kategori</th><th>Status</th><th>Terakhir</th></tr></thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        <tr>
                            <td>@if($ticket->manager_unread)<span class="hn-dot" title="Pesan baru"></span>@endif<a href="{{ route('apps.hunian.tickets.show', $ticket) }}"><strong>{{ $ticket->subject }}</strong></a></td>
                            <td>{{ $ticket->tenant?->full_name ?? '—' }}</td>
                            <td>{{ $ticket->room_number ?? '—' }}</td>
                            <td>{{ $ticket->category }}</td>
                            <td><span class="hn-status s-{{ $ticket->status }}">{{ $statuses[$ticket->status] }}</span></td>
                            <td style="white-space:nowrap">{{ $ticket->last_message_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $tickets->links() }}</div>
    @endif
</section>
@endsection
