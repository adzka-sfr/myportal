<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Ruang Alat' }} · Ruang Alat</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}"><span class="brand-mark">ra</span><span>ruang<span class="brand-light">alat</span></span></a>
        @auth
            <nav class="topnav" aria-label="Navigasi utama">
                <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Portal</a>
                @if(auth()->user()->is_superadmin)
                    <a href="{{ route('admin.users.index') }}" @class(['active' => request()->routeIs('admin.*')])>Kelola pengguna</a>
                @endif
            </nav>
            <div class="account-menu">
                <span class="account-name">{{ auth()->user()->name }}</span>
                @if(auth()->user()->is_superadmin)<span class="role-pill">Superadmin</span>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-quiet button-small" type="submit">Keluar</button></form>
            </div>
        @endauth
    </header>

    <main class="page-shell {{ auth()->check() ? '' : 'page-shell-auth' }}">
        @if(session('status'))<div class="notice notice-success" role="status">{{ session('status') }}</div>@endif
        @if($errors->any())
            <div class="notice notice-error" role="alert"><strong>Periksa kembali isianmu.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer"><span>Ruang Alat</span><span>Portal aplikasi pribadi</span></footer>
    <div class="page-loader" id="page-loader" aria-hidden="true"><div class="page-loader-text">ruang<span>alat</span></div></div>
    <script>
        (function () {
            var loader = document.getElementById('page-loader');
            var shownAt = 0;
            var show = function () {
                shownAt = Date.now();
                loader.setAttribute('aria-hidden', 'false');
                loader.classList.add('is-active');
            };
            var hide = function () {
                var remaining = Math.max(0, 350 - (Date.now() - shownAt));
                setTimeout(function () {
                    loader.classList.remove('is-active');
                    loader.setAttribute('aria-hidden', 'true');
                }, remaining);
            };
            window.addEventListener('pageshow', hide);
            document.addEventListener('click', function (e) {
                var a = e.target.closest && e.target.closest('a[href]');
                if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (a.target && a.target !== '_self' || a.hasAttribute('download')) return;
                var url = new URL(a.href, location.href);
                if (url.origin !== location.origin || url.protocol.indexOf('http') !== 0) return;
                if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
                show();
            });
            document.addEventListener('submit', function (e) {
                if (!e.defaultPrevented) setTimeout(function () { if (!e.defaultPrevented) show(); }, 0);
            });
        })();
    </script>
</body>
</html>
