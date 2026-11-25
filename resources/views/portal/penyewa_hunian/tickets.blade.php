@extends('layouts.app', ['title' => 'Keluhan · Penyewa Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Layanan</div>
        <h1>Keluhan</h1>
        <p class="muted">Sampaikan masalah di kamarmu, lampirkan foto, dan pantau balasan pengelola.</p>
    </div>
    <button type="button" class="button button-primary" id="new-ticket"><span aria-hidden="true">＋</span> Buat keluhan</button>
</div>

@include('portal.penyewa_hunian._nav')

<section class="hn-panel">
    @if($tickets->isEmpty())
        <div class="empty-state compact"><h3>Belum ada keluhan</h3><p>Ada yang perlu diperbaiki? Buat keluhan baru.</p></div>
    @else
        <div class="table-wrap">
            <table class="hn-table">
                <thead><tr><th>Keluhan</th><th>Kategori</th><th>Status</th><th>Terakhir</th></tr></thead>
                <tbody>
                    @foreach($tickets as $ticket)
                        <tr>
                            <td>@if($ticket->tenant_unread)<span class="hn-dot" title="Balasan baru"></span>@endif<a href="{{ route('apps.penyewa.tickets.show', $ticket) }}"><strong>{{ $ticket->subject }}</strong></a></td>
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

<dialog class="hn-dialog" id="ticket-dialog">
    <form method="POST" action="{{ route('apps.penyewa.tickets.store') }}" enctype="multipart/form-data">
        @csrf
        <div><div class="eyebrow">Keluhan baru</div><h2>Sampaikan keluhan</h2></div>
        @if($errors->any() && old('subject') !== null)
            <ul class="hn-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <div class="form-grid">
            <label>Kategori
                <select name="category" class="js-select2" required>
                    @foreach($categories as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>@endforeach
                </select>
            </label>
            <label>Judul<input name="subject" maxlength="150" required value="{{ old('subject') }}" placeholder="Mis. Keran kamar mandi bocor"></label>
            <label class="span-2">Deskripsi<textarea name="body" rows="4" maxlength="2000" required placeholder="Jelaskan masalahnya…">{{ old('body') }}</textarea></label>
            <label class="span-2">Foto (opsional)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"><span class="hn-hint">JPG, PNG, atau WebP, maksimal 4 MB.</span></label>
        </div>
        <div class="hn-dialog-actions">
            <button class="button button-quiet" type="button" data-close-dialog>Batal</button>
            <button class="button button-primary" type="submit">Kirim <span aria-hidden="true">→</span></button>
        </div>
    </form>
</dialog>

@include('portal.kelola_hunian._select2')
<script>
(function () {
    var dialog = document.getElementById('ticket-dialog');
    document.getElementById('new-ticket').addEventListener('click', function () { dialog.showModal(); });
    dialog.querySelector('[data-close-dialog]').addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });
    @if($errors->any() && old('subject') !== null)
        dialog.showModal();
    @endif
})();
</script>
@endsection
