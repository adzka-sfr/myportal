@extends('layouts.app', ['title' => 'Master kamar · Kelola Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Data master</div>
        <h1>Master kamar</h1>
        <p class="muted">Atur daftar kamar dan fasilitas. Penghuni dan pembayaran dikelola di halaman Monitoring.</p>
    </div>
    <span class="app-icon app-icon-hunian large-icon">⌂</span>
</div>

@include('portal.kelola_hunian._nav')

<div class="hn-split">
    <section class="hn-panel">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
            <div><h2>Daftar kamar</h2><p>{{ $rooms->count() }} kamar terdaftar.</p></div>
            <button type="button" class="button button-primary button-small" id="add-room"><span aria-hidden="true">＋</span> Tambah kamar</button>
        </div>
        @if($rooms->isEmpty())
            <div class="empty-state compact"><h3>Belum ada kamar</h3><p>Tambahkan kamar pertama.</p></div>
        @else
            <div class="table-wrap">
                <table class="hn-table">
                    <thead><tr><th>Kamar</th><th>Lantai</th><th>Harga / bulan</th><th>Fasilitas</th><th class="align-right">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($rooms as $room)
                            @php
                                $payload = [
                                    'id' => $room->id,
                                    'room_number' => $room->room_number,
                                    'floor' => $room->floor,
                                    'room_type' => $room->room_type,
                                    'monthly_price' => $room->monthly_price,
                                    'notes' => $room->notes,
                                    'facility_ids' => $room->facilities->pluck('id')->all(),
                                ];
                            @endphp
                            <tr>
                                <td><strong>{{ $room->room_number }}</strong>@if($room->room_type)<br><small class="hn-empty-text">{{ $room->room_type }}</small>@endif</td>
                                <td>{{ $room->floor }}</td>
                                <td>Rp {{ number_format($room->monthly_price, 0, ',', '.') }}</td>
                                <td><div class="hn-tags">@forelse($room->facilities as $facility)<span>{{ $facility->name }}</span>@empty<span class="hn-empty-text">—</span>@endforelse</div></td>
                                <td class="align-right">
                                    <div class="row-actions">
                                        <button type="button" class="button button-quiet button-small" data-edit-room="{{ json_encode($payload) }}">Ubah</button>
                                        <form method="POST" action="{{ route('apps.hunian.master.rooms.destroy', $room) }}" onsubmit="return confirm('Hapus kamar {{ addslashes($room->room_number) }}? Riwayat penghuninya ikut terhapus, log dan data pembayaran tetap tersimpan.')">@csrf @method('DELETE')<button type="submit" class="text-button danger-text">Hapus</button></form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <aside class="hn-panel">
        <h2>Fasilitas</h2>
        <p>Dipilih dengan checklist saat membuat kamar.</p>
        @if($facilities->isNotEmpty())
            <ul class="hn-fac-list">
                @foreach($facilities as $facility)
                    <li>
                        <form method="POST" action="{{ route('apps.hunian.master.facilities.update', $facility) }}">@csrf @method('PUT')<input name="name" value="{{ $facility->name }}" maxlength="80" required aria-label="Nama fasilitas"><button class="text-button" type="submit">Simpan</button></form>
                        <small>{{ $facility->rooms_count }} kamar</small>
                        <form method="POST" action="{{ route('apps.hunian.master.facilities.destroy', $facility) }}" onsubmit="return confirm('Hapus fasilitas {{ addslashes($facility->name) }}? Fasilitas dilepas dari {{ $facility->rooms_count }} kamar.')">@csrf @method('DELETE')<button class="text-button danger-text" type="submit" aria-label="Hapus {{ $facility->name }}">Hapus</button></form>
                    </li>
                @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('apps.hunian.master.facilities.store') }}" class="hn-fac-list" style="flex-direction:row">@csrf
            <input name="name" maxlength="80" required placeholder="Fasilitas baru, mis. Water heater" style="flex:1;min-width:0;font:inherit;padding:8px 10px;border:1px solid var(--line);border-radius:9px">
            <button class="button button-quiet button-small" type="submit">＋ Tambah</button>
        </form>
    </aside>
</div>

@include('portal.kelola_hunian._form')

<script>
(function () {
    var dialog = document.getElementById('room-dialog');
    var form = document.getElementById('room-form');
    var method = document.getElementById('room-method');
    var editId = document.getElementById('room-edit-id');
    var title = document.getElementById('room-title');
    var storeUrl = @json(route('apps.hunian.master.rooms.store'));
    var updateUrl = @json(route('apps.hunian.master.rooms.update', ['room' => '__ID__']));

    function fill(r) {
        ['room_number', 'floor', 'room_type', 'monthly_price', 'notes'].forEach(function (k) { form.elements[k].value = r[k] == null ? '' : r[k]; });
        var ids = (r.facility_ids || []).map(String);
        form.querySelectorAll('input[name="facilities[]"]').forEach(function (c) { c.checked = ids.indexOf(c.value) !== -1; });
    }

    function open(room) {
        form.action = room ? updateUrl.replace('__ID__', room.id) : storeUrl;
        method.disabled = !room;
        editId.value = room ? room.id : '';
        title.textContent = room ? 'Ubah kamar ' + room.room_number : 'Tambah kamar';
        fill(room || { floor: 1, monthly_price: 0 });
        dialog.showModal();
    }

    document.getElementById('add-room').addEventListener('click', function () { open(null); });
    document.querySelectorAll('[data-edit-room]').forEach(function (btn) {
        btn.addEventListener('click', function () { open(JSON.parse(btn.dataset.editRoom)); });
    });
    document.querySelector('[data-close-dialog]').addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });

    // Buka kembali dialog beserta isian lama saat validasi gagal.
    @if($errors->any() && old('_dialog') === 'room')
        var id = editId.value;
        form.action = id ? updateUrl.replace('__ID__', id) : storeUrl;
        method.disabled = !id;
        title.textContent = id ? 'Ubah kamar' : 'Tambah kamar';
        dialog.showModal();
    @endif
})();
</script>
@endsection
