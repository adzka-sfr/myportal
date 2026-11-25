@extends('layouts.app', ['title' => 'Informasi penghuni · Kelola Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Data penghuni</div>
        <h1>Informasi penghuni</h1>
        <p class="muted">Daftarkan penghuni beserta data KTP. Hanya penghuni aktif yang muncul sebagai pilihan saat check-in atau booking.</p>
    </div>
    <span class="app-icon app-icon-hunian large-icon">⌂</span>
</div>

@include('portal.kelola_hunian._nav')

<section class="hn-stats" aria-label="Ringkasan penghuni">
    <div class="hn-stat"><span>Terdaftar</span><strong>{{ $counts['total'] }}</strong><small>seluruh penghuni</small></div>
    <div class="hn-stat"><span>Aktif</span><strong>{{ $counts['active'] }}</strong><small>bisa dipilih di Monitoring</small></div>
    <div class="hn-stat"><span>Punya akun login</span><strong>{{ $counts['with_account'] }}</strong><small>dapat memakai aplikasi Penyewa</small></div>
</section>

<section class="hn-panel">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <form method="GET" action="{{ route('apps.hunian.tenants') }}" class="hn-logfilter" style="margin-bottom:0">
            <label>Cari<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="120" placeholder="Nama, NIK, atau telepon" style="font:inherit;padding:8px 10px;border:1px solid var(--line);border-radius:10px;background:#fff"></label>
            <label>Status
                <select name="status" class="js-select2" data-allow-clear="1" data-placeholder="Semua status">
                    <option value=""></option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>Aktif</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>Nonaktif</option>
                </select>
            </label>
            <button class="button button-quiet button-small" type="submit">Terapkan</button>
            @if(array_filter($filters))<a class="text-button" href="{{ route('apps.hunian.tenants') }}">Reset</a>@endif
        </form>
        <button type="button" class="button button-primary button-small" id="add-tenant"><span aria-hidden="true">＋</span> Tambah penghuni</button>
    </div>

    @if($tenants->isEmpty())
        <div class="empty-state compact"><h3>Belum ada penghuni</h3><p>Tambahkan penghuni pertama untuk mulai check-in.</p></div>
    @else
        <div class="table-wrap" style="margin-top:16px">
            <table class="hn-table">
                <thead><tr><th>Penghuni</th><th>NIK</th><th>Kamar</th><th>Akun login</th><th>Status</th><th class="align-right">Aksi</th></tr></thead>
                <tbody>
                    @foreach($tenants as $tenant)
                        @php
                            $occ = $tenant->activeOccupancy ?? $tenant->reservation;
                            $payload = [
                                'id' => $tenant->id,
                                'full_name' => $tenant->full_name,
                                'nik' => $tenant->nik,
                                'phone' => $tenant->phone,
                                'ktp_address' => $tenant->ktp_address,
                                'is_active' => $tenant->is_active,
                                'email' => $tenant->user?->email,
                                'has_account' => $tenant->user_id !== null,
                                'ktp_url' => $tenant->ktp_photo_path ? route('apps.hunian.tenants.ktp', $tenant) : null,
                            ];
                        @endphp
                        <tr>
                            <td><strong>{{ $tenant->full_name }}</strong><br><small class="hn-empty-text">{{ $tenant->phone }}</small></td>
                            <td style="white-space:nowrap">{{ $tenant->maskedNik() }}</td>
                            <td>
                                @if($occ)
                                    Kamar {{ $occ->room?->room_number ?? '—' }}<br><small class="hn-empty-text">{{ $occ->status === 'active' ? 'Menghuni' : 'Booking' }}</small>
                                @else
                                    <span class="hn-empty-text">—</span>
                                @endif
                            </td>
                            <td>{{ $tenant->user?->email ?? '—' }}</td>
                            <td><span class="hn-act {{ $tenant->is_active ? 'act-check_in' : 'act-check_out' }}">{{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td class="align-right">
                                <div class="row-actions">
                                    <button type="button" class="button button-quiet button-small" data-edit-tenant="{{ json_encode($payload) }}">Ubah</button>
                                    @if($tenant->ktp_photo_path)<a class="text-button" href="{{ route('apps.hunian.tenants.ktp', $tenant) }}" target="_blank" rel="noopener">KTP</a>@endif
                                    <form method="POST" action="{{ route('apps.hunian.tenants.active', $tenant) }}" onsubmit="return confirm('{{ $tenant->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ addslashes($tenant->full_name) }}?')">@csrf @method('PATCH')<button type="submit" class="text-button">{{ $tenant->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                    <form method="POST" action="{{ route('apps.hunian.tenants.destroy', $tenant) }}" onsubmit="return confirm('Hapus {{ addslashes($tenant->full_name) }} beserta akun loginnya? Tidak dapat dibatalkan.')">@csrf @method('DELETE')<button type="submit" class="text-button danger-text">Hapus</button></form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $tenants->links() }}</div>
    @endif
</section>

<dialog class="hn-dialog" id="tenant-dialog">
    <form method="POST" id="tenant-form" action="{{ route('apps.hunian.tenants.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_method" value="PUT" id="tenant-method" disabled>
        <input type="hidden" name="_dialog" value="tenant">
        <input type="hidden" name="_edit_id" id="tenant-edit-id" value="{{ old('_edit_id') }}">
        <div><div class="eyebrow">Penghuni</div><h2 id="tenant-title">Tambah penghuni</h2></div>
        @if(old('_dialog') === 'tenant' && $errors->any())
            <ul class="hn-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif

        <div class="form-grid">
            <label class="span-2">Nama lengkap (sesuai KTP)<input name="full_name" maxlength="120" required value="{{ old('full_name') }}"></label>
            <label>NIK<input name="nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" minlength="16" required value="{{ old('nik') }}" placeholder="16 digit"></label>
            <label>No. telepon<input name="phone" maxlength="30" required value="{{ old('phone') }}" placeholder="08xxxxxxxxxx"></label>
            <label class="span-2">Alamat sesuai KTP<textarea name="ktp_address" rows="2" maxlength="500" required>{{ old('ktp_address') }}</textarea></label>
            <label class="span-2">Foto KTP
                <input type="file" name="ktp_photo" id="tenant-ktp" accept="image/jpeg,image/png,image/webp">
                <span class="hn-hint" id="tenant-ktp-hint">JPG, PNG, atau WebP, maksimal 4 MB. Disimpan privat.</span>
            </label>
            <label class="span-2" style="flex-direction:row;align-items:center;gap:10px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" id="tenant-active" style="width:auto" @checked(old('is_active', '1') == '1')> Penghuni aktif
            </label>
        </div>

        <fieldset>
            <legend>Akun login (opsional)</legend>
            <p class="hn-hint" id="tenant-account-hint" style="margin:0 0 10px">Isi email dan kata sandi agar penghuni dapat masuk ke aplikasi Penyewa.</p>
            <div class="form-grid">
                <label>Email<input type="email" name="email" maxlength="190" autocomplete="off" value="{{ old('email') }}"></label>
                <label>Kata sandi<input type="password" name="password" minlength="12" maxlength="100" autocomplete="new-password"><span class="hn-hint">Minimal 12 karakter.</span></label>
            </div>
        </fieldset>

        <div class="hn-dialog-actions">
            <button class="button button-quiet" type="button" data-close-dialog>Batal</button>
            <button class="button button-primary" type="submit">Simpan <span aria-hidden="true">→</span></button>
        </div>
    </form>
</dialog>

@include('portal.kelola_hunian._select2')
<script>
(function () {
    var dialog = document.getElementById('tenant-dialog');
    var form = document.getElementById('tenant-form');
    var method = document.getElementById('tenant-method');
    var editId = document.getElementById('tenant-edit-id');
    var title = document.getElementById('tenant-title');
    var ktp = document.getElementById('tenant-ktp');
    var ktpHint = document.getElementById('tenant-ktp-hint');
    var accountHint = document.getElementById('tenant-account-hint');
    var storeUrl = @json(route('apps.hunian.tenants.store'));
    var updateUrl = @json(route('apps.hunian.tenants.update', ['tenant' => '__ID__']));

    function open(t) {
        form.action = t ? updateUrl.replace('__ID__', t.id) : storeUrl;
        method.disabled = !t;
        editId.value = t ? t.id : '';
        title.textContent = t ? 'Ubah ' + t.full_name : 'Tambah penghuni';
        form.full_name.value = t ? t.full_name : '';
        form.nik.value = t ? t.nik : '';
        form.phone.value = t ? t.phone : '';
        form.ktp_address.value = t ? t.ktp_address : '';
        form.email.value = t && t.email ? t.email : '';
        form.password.value = '';
        document.getElementById('tenant-active').checked = t ? t.is_active : true;
        ktp.value = '';
        ktp.required = !t || !t.ktp_url;
        ktpHint.innerHTML = t && t.ktp_url
            ? 'KTP sudah tersimpan (<a href="' + t.ktp_url + '" target="_blank" rel="noopener">lihat</a>). Pilih file baru hanya untuk mengganti.'
            : 'JPG, PNG, atau WebP, maksimal 4 MB. Disimpan privat.';
        accountHint.textContent = t && t.has_account
            ? 'Akun login sudah terhubung. Kosongkan kata sandi jika tidak ingin mengubahnya.'
            : 'Isi email dan kata sandi agar penghuni dapat masuk ke aplikasi Penyewa.';
        dialog.showModal();
    }

    document.getElementById('add-tenant').addEventListener('click', function () { open(null); });
    document.querySelectorAll('[data-edit-tenant]').forEach(function (btn) {
        btn.addEventListener('click', function () { open(JSON.parse(btn.dataset.editTenant)); });
    });
    dialog.querySelector('[data-close-dialog]').addEventListener('click', function () { dialog.close(); });
    dialog.addEventListener('click', function (e) { if (e.target === dialog) dialog.close(); });

    // Buka kembali dialog beserta isian lama saat validasi gagal (file KTP perlu dipilih ulang).
    @if($errors->any() && old('_dialog') === 'tenant')
        (function () {
            var id = editId.value;
            form.action = id ? updateUrl.replace('__ID__', id) : storeUrl;
            method.disabled = !id;
            title.textContent = id ? 'Ubah penghuni' : 'Tambah penghuni';
            ktp.required = !id;
            dialog.showModal();
        })();
    @endif
})();
</script>
@endsection
