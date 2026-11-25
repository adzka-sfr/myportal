@php($editing = $user->exists)
@extends('layouts.app', ['title' => $editing ? 'Atur pengguna' : 'Tambah pengguna'])

@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('admin.users.index') }}">← Kembali ke pengguna</a><div class="eyebrow">Administrasi</div><h1>{{ $editing ? 'Atur akun' : 'Tambah pengguna' }}</h1><p class="muted">{{ $editing ? 'Perbarui informasi dan akses aplikasi pengguna.' : 'Buat akun, lalu tentukan akses awalnya.' }}</p></div></div>
<form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="admin-form">
    @csrf @if($editing) @method('PUT') @endif
    <section class="panel form-panel"><div class="panel-heading"><div><h2>Informasi akun</h2><p>Pengguna masuk memakai email dan kata sandi ini.</p></div><span class="step-number">01</span></div>
        <div class="form-grid"><label>Nama lengkap<input name="name" value="{{ old('name', $user->name) }}" maxlength="120" required></label><label>Email<input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="190" required></label>
            <label>{{ $editing ? 'Kata sandi baru (opsional)' : 'Kata sandi awal' }}<input type="password" name="password" {{ $editing ? '' : 'required' }} minlength="12" autocomplete="new-password"><small>Minimal 12 karakter. {{ $editing ? 'Kosongkan jika tidak ingin mengubah.' : '' }}</small></label><label>Konfirmasi kata sandi<input type="password" name="password_confirmation" {{ $editing ? '' : 'required' }} minlength="12" autocomplete="new-password"></label></div>
        <div class="form-grid form-grid-checks"><label class="check-card"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing ? $user->is_active : true))><span><strong>Akun aktif</strong><small>Pengguna dapat masuk ke portal.</small></span></label><label class="check-card"><input type="hidden" name="is_superadmin" value="0"><input type="checkbox" name="is_superadmin" value="1" @checked(old('is_superadmin', $editing ? $user->is_superadmin : false))><span><strong>Jadikan Superadmin</strong><small>Dapat mengelola semua akun dan aplikasi.</small></span></label></div>
    </section>

    <section class="panel form-panel"><div class="panel-heading"><div><h2>Akses aplikasi & fitur</h2><p>Centang aplikasinya, lalu pilih fitur yang boleh digunakan di dalamnya.</p></div><span class="step-number">02</span></div>
        <div class="permission-list">
            @foreach(collect(config('portal.applications'))->reject(fn ($app) => ($app['superadmin_only'] ?? false) || ($app['resident_only'] ?? false)) as $app => $definition)
                <article class="permission-app"><label class="permission-app-head"><input type="checkbox" name="applications[]" value="{{ $app }}" @checked(in_array($app, old('applications', $applicationAccess), true))><span class="app-icon app-icon-{{ $app }}">{{ $definition['icon'] }}</span><span><strong>{{ $definition['name'] }}</strong><small>{{ $definition['description'] }}</small></span></label>
                    <div class="permission-features">@foreach($definition['features'] as $feature => $featureDefinition)<label class="permission-feature"><input type="checkbox" name="features[{{ $app }}][{{ $feature }}]" value="1" @checked(old("features.{$app}.{$feature}", $featureAccess["{$app}.{$feature}"] ?? false))><span><strong>{{ $featureDefinition['name'] }}</strong><small>{{ $featureDefinition['description'] }}</small></span></label>@endforeach</div>
                </article>
            @endforeach
        </div>
        <div class="form-actions"><a class="button button-quiet" href="{{ route('admin.users.index') }}">Batal</a><button class="button button-primary" type="submit">{{ $editing ? 'Simpan perubahan' : 'Buat akun' }} <span aria-hidden="true">→</span></button></div>
    </section>
</form>
@endsection
