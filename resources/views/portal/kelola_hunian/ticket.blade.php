@extends('layouts.app', ['title' => $ticket->subject.' · Keluhan'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('apps.hunian.tickets') }}">← Kembali ke keluhan</a>
        <div class="eyebrow">{{ $ticket->category }}</div>
        <h1>{{ $ticket->subject }}</h1>
        <p class="muted">{{ $ticket->tenant?->full_name ?? 'Penghuni' }}@if($ticket->room_number) · Kamar {{ $ticket->room_number }}@endif · dibuat {{ $ticket->created_at->format('d M Y H:i') }}</p>
    </div>
    <span class="hn-status s-{{ $ticket->status }}">{{ $statuses[$ticket->status] }}</span>
</div>

<div class="hn-split">
    <section class="hn-panel">
        @include('portal.kelola_hunian._thread', [
            'mine' => 'manager',
            'imageRoute' => 'apps.hunian.tickets.image',
            'replyUrl' => route('apps.hunian.tickets.reply', $ticket),
        ])
    </section>

    <aside class="hn-panel">
        <h2>Status keluhan</h2>
        <p>Penghuni melihat perubahan status di aplikasinya.</p>
        <form method="POST" action="{{ route('apps.hunian.tickets.status', $ticket) }}" class="hn-field">
            @csrf @method('PATCH')
            <select name="status" class="js-select2">
                @foreach($statuses as $value => $label)<option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>@endforeach
            </select>
            <button class="button button-primary button-small" type="submit" style="margin-top:10px">Simpan status</button>
        </form>
        @if($ticket->tenant)
            <h2 style="margin-top:22px">Penghuni</h2>
            <dl class="hn-kv" style="grid-template-columns:90px 1fr">
                <dt>Nama</dt><dd>{{ $ticket->tenant->full_name }}</dd>
                <dt>Telepon</dt><dd>{{ $ticket->tenant->phone }}</dd>
            </dl>
        @endif
    </aside>
</div>

@include('portal.kelola_hunian._select2')
@endsection
