@extends('layouts.app', ['title' => 'Checklist Belanja'])

@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a><div class="eyebrow">Rencana belanja</div><h1>Checklist Belanja</h1><p class="muted">Daftar belanja tersimpan untuk akunmu sendiri.</p></div><span class="app-icon app-icon-shopping large-icon">✓</span></div>
@if(auth()->user()->canUseFeature('shopping', 'checklist'))
<section class="shopping-layout">
    @if(auth()->user()->canUseFeature('shopping', 'multiple_lists'))<aside class="panel list-sidebar"><div class="panel-heading"><div><h2>Daftarku</h2><p>{{ $lists->count() }} daftar tersimpan</p></div></div><nav class="list-nav">@foreach($lists as $savedList)<a href="{{ route('apps.shopping', ['list' => $savedList->id]) }}" @class(['list-nav-item', 'selected' => $list->id === $savedList->id])><span>{{ $savedList->name }}</span><small>{{ $savedList->items_count ?? $savedList->items->count() }}</small></a>@endforeach</nav>
        <form method="POST" action="{{ route('apps.shopping.lists.store') }}" class="new-list-form">@csrf<label>Daftar baru<input name="name" maxlength="100" placeholder="Mis. Kebutuhan dapur" required></label><button class="button button-quiet button-wide" type="submit">＋ Buat daftar</button></form>
    </aside>@endif
    <div class="panel shopping-panel"><div class="panel-heading"><div><div class="eyebrow">Daftar aktif</div><h2>{{ $list->name }}</h2><p>{{ $list->items->where('is_checked', false)->count() }} barang belum dicentang</p></div>
        @if(auth()->user()->canUseFeature('shopping', 'multiple_lists') && $lists->count() > 1)<form method="POST" action="{{ route('apps.shopping.lists.destroy', $list) }}" onsubmit="return confirm('Hapus daftar ini dan seluruh barangnya?')">@csrf @method('DELETE')<button class="text-button danger-text" type="submit">Hapus daftar</button></form>@endif
    </div>
    <form method="POST" action="{{ route('apps.shopping.items.store') }}" class="shopping-add">@csrf<input type="hidden" name="list_id" value="{{ $list->id }}"><label class="sr-only" for="item-name">Nama barang</label><input id="item-name" name="name" maxlength="160" placeholder="Tambah barang ke daftar…" required><input name="quantity" maxlength="60" class="quantity-input" placeholder="Jumlah"><button class="button button-primary" type="submit">Tambah <span aria-hidden="true">＋</span></button></form>
    @if($list->items->isEmpty())<div class="empty-state compact"><div class="empty-icon">＋</div><h3>Daftarmu masih kosong</h3><p>Tambahkan barang yang perlu dibeli.</p></div>@else
        <ul class="shopping-items">@foreach($list->items as $item)<li @class(['shopping-item', 'checked' => $item->is_checked])><form method="POST" action="{{ route('apps.shopping.items.toggle', $item) }}">@csrf @method('PATCH')<button class="item-check" type="submit" aria-label="{{ $item->is_checked ? 'Tandai belum dibeli' : 'Tandai sudah dibeli' }}">{{ $item->is_checked ? '✓' : '' }}</button></form><div class="item-name"><strong>{{ $item->name }}</strong>@if($item->quantity)<small>{{ $item->quantity }}</small>@endif</div><form method="POST" action="{{ route('apps.shopping.items.destroy', $item) }}" onsubmit="return confirm('Hapus {{ addslashes($item->name) }} dari daftar?')">@csrf @method('DELETE')<button class="text-button danger-text" type="submit" aria-label="Hapus {{ $item->name }}">Hapus</button></form></li>@endforeach</ul>
    @endif
    </div>
</section>
@else<div class="empty-state"><h3>Fitur belum diberikan</h3><p>Hubungi Superadmin untuk meminta izin menggunakan checklist.</p></div>@endif
@endsection
