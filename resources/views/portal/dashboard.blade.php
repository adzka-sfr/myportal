@extends('layouts.app', ['title' => 'Portal'])

@section('content')
<section class="hero hero-dashboard">
    <div><div class="eyebrow">Ruang kerja digitalmu</div><h1>Halo, {{ Str::before(auth()->user()->name, ' ') }}.</h1><p class="muted">Pilih aplikasi untuk mulai. Akses yang tampil disesuaikan untuk akunmu.</p></div>
    <div class="hero-orbit" aria-hidden="true"><span>ra</span><i>✦</i></div>
</section>

@if(auth()->user()->is_superadmin)
    <section class="admin-banner">
        <div><span class="admin-banner-icon">✳</span><div><strong>Mode Superadmin</strong><p>Atur akun, aplikasi, dan fitur yang dapat digunakan setiap pengguna.</p></div></div>
        <a class="button button-dark" href="{{ route('admin.users.index') }}">Kelola pengguna <span aria-hidden="true">→</span></a>
    </section>
@endif

<section class="section-heading"><div><div class="eyebrow">Koleksi aplikasi</div><h2>Aplikasi untukmu <span class="count-badge">{{ $applications->count() }}</span></h2></div></section>
@if($applications->isEmpty())
    <div class="empty-state"><div class="empty-icon">⌁</div><h3>Belum ada aplikasi yang diberikan</h3><p>Hubungi Superadmin untuk meminta akses.</p></div>
@else
    <div class="app-grid">
        @foreach($applications as $app)
            <a class="app-card" href="{{ route($app['route']) }}">
                <div class="app-card-top"><span class="app-icon app-icon-{{ $app['slug'] }}">{{ $app['icon'] }}</span><span class="card-arrow" aria-hidden="true">↗</span></div>
                <h3>{{ $app['name'] }}</h3><p>{{ $app['description'] }}</p>
                <div class="feature-tags">@foreach($app['features'] as $feature => $definition)@if(auth()->user()->canUseFeature($app['slug'], $feature))<span>{{ $definition['name'] }}</span>@endif @endforeach</div>
                <span class="card-link">Buka aplikasi <span aria-hidden="true">→</span></span>
            </a>
        @endforeach
    </div>
@endif
<div class="dashboard-note"><span>✦</span> Akses aplikasi dan fitur dikelola oleh Superadmin.</div>
@endsection
