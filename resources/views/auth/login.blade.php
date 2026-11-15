@extends('layouts.app', ['title' => 'Masuk'])

@section('content')
<section class="auth-card">
    <div class="eyebrow">Portal pribadi</div>
    <h1>Selamat datang.</h1>
    <p class="muted">Masuk satu kali untuk membuka aplikasi yang tersedia untuk akunmu.</p>
    <form method="POST" action="{{ route('login.store') }}" class="stack-form">
        @csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
        <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label>
        <label class="check-line"><input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini</label>
        <button class="button button-primary button-wide" type="submit">Masuk ke portal <span aria-hidden="true">→</span></button>
    </form>
    <p class="auth-footnote">Akun dibuat dan dikelola oleh Superadmin.</p>
</section>
@endsection
