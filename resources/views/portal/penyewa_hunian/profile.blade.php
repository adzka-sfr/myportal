@extends('layouts.app', ['title' => 'Profil · Penyewa Hunian'])

@section('content')
@include('portal.kelola_hunian._styles')

<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a>
        <div class="eyebrow">Akun</div>
        <h1>Profil penghuni</h1>
        <p class="muted">Data dari pendaftaran. Hubungi pengelola bila ada yang perlu diperbaiki.</p>
    </div>
    <span class="app-icon app-icon-penyewa large-icon">☺</span>
</div>

@include('portal.penyewa_hunian._nav')

<div class="hn-split">
    <div style="display:flex;flex-direction:column;gap:20px">
        <section class="hn-panel">
            <h2>Data diri</h2>
            <p>NIK ditampilkan sebagian demi keamanan.</p>
            <dl class="hn-kv">
                <dt>Nama</dt><dd>{{ $tenant->full_name }}</dd>
                <dt>NIK</dt><dd>{{ $tenant->maskedNik() }}</dd>
                <dt>No. telepon</dt><dd>{{ $tenant->phone }}</dd>
                <dt>Alamat KTP</dt><dd>{{ $tenant->ktp_address }}</dd>
                <dt>Email login</dt><dd>{{ $email }}</dd>
            </dl>
        </section>

        <section class="hn-panel">
            <h2>Riwayat hunian</h2>
            <p>Kamar yang pernah dan sedang kamu huni.</p>
            @if($stays->isEmpty())
                <span class="hn-empty-text">Belum ada riwayat.</span>
            @else
                <table class="hn-table">
                    <thead><tr><th>Kamar</th><th>Status</th><th>Masuk</th><th>Keluar</th></tr></thead>
                    <tbody>
                        @foreach($stays as $stay)
                            <tr>
                                <td>{{ $stay->room?->room_number ?? '—' }}</td>
                                <td>{{ ['active' => 'Menghuni', 'reserved' => 'Booking', 'ended' => 'Selesai'][$stay->status] }}</td>
                                <td>{{ $stay->check_in_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $stay->ended_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>

    <aside class="hn-panel">
        <h2>Ubah kata sandi</h2>
        <p>Minimal 12 karakter.</p>
        <form method="POST" action="{{ route('apps.penyewa.password') }}" style="display:flex;flex-direction:column;gap:12px">
            @csrf @method('PUT')
            <label class="hn-field">Kata sandi saat ini<input type="password" name="current_password" required autocomplete="current-password"></label>
            <label class="hn-field">Kata sandi baru<input type="password" name="password" required minlength="12" autocomplete="new-password"></label>
            <label class="hn-field">Ulangi kata sandi baru<input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password"></label>
            <button class="button button-primary" type="submit">Simpan kata sandi</button>
        </form>
    </aside>
</div>
@endsection
