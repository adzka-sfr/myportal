@extends('layouts.app', ['title' => 'Kalkulator Diskon'])

@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a><div class="eyebrow">Harga & penghematan</div><h1>Kalkulator Diskon</h1><p class="muted">Cari tahu harga akhir dan jumlah yang kamu hemat.</p></div><span class="app-icon app-icon-discount large-icon">％</span></div>
@if(auth()->user()->canUseFeature('discount', 'basic'))
<section class="tool-grid tool-grid-narrow">
    <div class="panel tool-panel"><div class="panel-heading"><div><h2>Diskon tunggal</h2><p>Masukkan harga awal dan besaran potongan.</p></div></div>
        <form method="POST" action="{{ route('apps.discount.calculate') }}" class="stack-form">@csrf<div class="form-grid"><label>Harga awal <span class="unit">Rp</span><input type="number" name="price" step="any" min="0" value="{{ old('price', 150000) }}" required></label><label>Diskon <span class="unit">%</span><input type="number" name="discount" step="any" min="0" max="100" value="{{ old('discount', 20) }}" required></label></div><button class="button button-primary" type="submit">Hitung harga <span aria-hidden="true">→</span></button></form>
        @if(session('result'))<div class="result-card"><span class="result-label">Harga setelah diskon</span><strong>Rp {{ number_format(session('result.final'), 0, ',', '.') }}</strong><div class="result-meta">Hemat Rp {{ number_format(session('result.saved'), 0, ',', '.') }} · diskon efektif {{ number_format(session('result.effective'), 2, ',', '.') }}%</div></div>@endif
    </div>
    @if(auth()->user()->canUseFeature('discount', 'stacked'))<div class="panel tool-panel"><div class="panel-heading"><div><h2>Diskon bertingkat</h2><p>Potongan kedua diterapkan pada harga setelah potongan pertama.</p></div></div>
        <form method="POST" action="{{ route('apps.discount.stacked') }}" class="stack-form">@csrf<div class="form-grid"><label>Harga awal <span class="unit">Rp</span><input type="number" name="price" step="any" min="0" value="{{ old('price', 150000) }}" required></label><label>Diskon pertama <span class="unit">%</span><input type="number" name="discount" step="any" min="0" max="100" value="{{ old('discount', 20) }}" required></label><label>Diskon kedua <span class="unit">%</span><input type="number" name="second_discount" step="any" min="0" max="100" value="{{ old('second_discount', 10) }}" required></label></div><button class="button button-primary" type="submit">Hitung bertingkat <span aria-hidden="true">→</span></button></form>
        @if(session('stacked_result'))<div class="result-card"><span class="result-label">Harga setelah dua diskon</span><strong>Rp {{ number_format(session('stacked_result.final'), 0, ',', '.') }}</strong><div class="result-meta">Hemat Rp {{ number_format(session('stacked_result.saved'), 0, ',', '.') }} · diskon efektif {{ number_format(session('stacked_result.effective'), 2, ',', '.') }}%</div></div>@endif
    </div>@endif
</section>
<p class="formula-note">Diskon bertingkat dihitung berurutan, bukan dengan menjumlahkan persentase diskon.</p>
@else<div class="empty-state"><h3>Fitur belum diberikan</h3><p>Hubungi Superadmin untuk meminta izin menggunakan kalkulator ini.</p></div>@endif
@endsection
