@extends('layouts.app', ['title' => 'Log aktivitas · Kelola Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Riwayat</div>
        <h1>Log aktivitas</h1>
        <p class="muted">Seluruh perubahan penghuni, pembayaran, perbaikan, dan master tercatat di sini.</p>
    </div>
    <span class="app-icon app-icon-hunian large-icon">⌂</span>
</div>

@include('portal.kelola_hunian._nav')

<section class="hn-panel">
    <form method="GET" action="{{ route('apps.hunian.logs') }}" class="hn-logfilter">
        <label>Kamar
            <select name="room"><option value="">Semua kamar</option>@foreach($roomNumbers as $number)<option value="{{ $number }}" @selected(($filters['room'] ?? null) === $number)>{{ $number }}</option>@endforeach</select>
        </label>
        <label>Aktivitas
            <select name="action"><option value="">Semua aktivitas</option>@foreach($actions as $value => $label)<option value="{{ $value }}" @selected(($filters['action'] ?? null) === $value)>{{ $label }}</option>@endforeach</select>
        </label>
        <button class="button button-quiet button-small" type="submit">Terapkan</button>
        @if(! empty($filters['room']) || ! empty($filters['action']))<a class="text-button" href="{{ route('apps.hunian.logs') }}">Reset</a>@endif
    </form>

    @if($logs->isEmpty())
        <div class="empty-state compact"><h3>Belum ada log</h3><p>Aktivitas akan muncul otomatis.</p></div>
    @else
        <div class="table-wrap">
            <table class="hn-table">
                <thead><tr><th>Waktu</th><th>Kamar</th><th>Aktivitas</th><th>Keterangan</th><th>Penghuni</th><th>Nominal</th><th>Oleh</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td style="white-space:nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td>{{ $log->room_number ?? '—' }}</td>
                            <td><span class="hn-act act-{{ $log->action }}">{{ $actions[$log->action] ?? $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td>{{ $log->occupant_name ?? '—' }}</td>
                            <td style="white-space:nowrap">{{ $log->amount !== null ? 'Rp '.number_format($log->amount, 0, ',', '.') : '—' }}</td>
                            <td>{{ $log->actor_name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $logs->links() }}</div>
    @endif
</section>
@endsection
