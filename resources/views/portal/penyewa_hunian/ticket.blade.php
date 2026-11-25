@extends('layouts.app', ['title' => $ticket->subject.' · Keluhan'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('apps.penyewa.tickets') }}">← Kembali ke keluhan</a>
        <div class="eyebrow">{{ $ticket->category }}</div>
        <h1>{{ $ticket->subject }}</h1>
        <p class="muted">Dibuat {{ $ticket->created_at->format('d M Y H:i') }}@if($ticket->room_number) · Kamar {{ $ticket->room_number }}@endif</p>
    </div>
    <span class="hn-status s-{{ $ticket->status }}">{{ $statuses[$ticket->status] }}</span>
</div>

<section class="hn-panel">
    @if($ticket->status === 'resolved')
        <div class="hn-alert soon" style="margin-bottom:14px">Keluhan ini sudah ditandai selesai. Membalas akan membukanya kembali.</div>
    @endif
    @include('portal.kelola_hunian._thread', [
        'mine' => 'tenant',
        'imageRoute' => 'apps.penyewa.tickets.image',
        'replyUrl' => route('apps.penyewa.tickets.reply', $ticket),
    ])
</section>
@endsection
