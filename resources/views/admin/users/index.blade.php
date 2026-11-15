@extends('layouts.app', ['title' => 'Kelola pengguna'])

@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a><div class="eyebrow">Administrasi</div><h1>Pengguna & akses</h1><p class="muted">Kelola akun, pilih aplikasi, lalu tentukan fitur yang tersedia bagi setiap orang.</p></div><a class="button button-primary" href="{{ route('admin.users.create') }}"><span aria-hidden="true">＋</span> Tambah pengguna</a></div>
<section class="panel table-panel">
    <div class="panel-heading"><div><h2>Semua akun</h2><p>{{ $users->total() }} akun terdaftar</p></div><span class="subtle-label">{{ $users->count() }} di halaman ini</span></div>
    @if($users->isEmpty())<div class="empty-state compact"><h3>Belum ada pengguna</h3></div>@else
    <div class="table-wrap"><table><thead><tr><th>Nama</th><th>Peran</th><th>Status</th><th>Dibuat</th><th class="align-right">Aksi</th></tr></thead><tbody>
        @foreach($users as $user)
        <tr><td><div class="person-cell"><span class="avatar">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span><span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span></div></td>
            <td>@if($user->is_superadmin)<span class="role-pill">Superadmin</span>@else<span class="subtle-label">Pengguna</span>@endif</td>
            <td><span class="status-dot {{ $user->is_active ? 'is-active' : 'is-inactive' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
            <td class="muted">{{ $user->created_at->format('d M Y') }}</td>
            <td class="align-right"><div class="row-actions"><a class="button button-quiet button-small" href="{{ route('admin.users.edit', $user) }}">Atur akses</a>@if(! $user->is(auth()->user()))<form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }}?')">@csrf @method('DELETE')<button type="submit" class="text-button danger-text">Hapus</button></form>@endif</div></td>
        </tr>
        @endforeach
    </tbody></table></div>
    <div class="pagination-wrap">{{ $users->links() }}</div>
    @endif
</section>
@endsection
