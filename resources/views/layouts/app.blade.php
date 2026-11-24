<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'ATARA' }} · ATARA</title>
    <meta name="theme-color" content="#3A0B08">
    <link rel="icon" type="image/svg+xml" href="{{ asset('branding/atara-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
</head>
<body>
    <header class="topbar">
        <a class="brand" href="{{ auth()->check() ? route('dashboard') : route('login') }}" aria-label="ATARA beranda"><img class="brand-mark" src="{{ asset('branding/atara-mark.svg') }}" alt=""><span>ATARA</span></a>
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
    <footer class="site-footer"><span>ATARA</span><span>Portal aplikasi pribadi</span></footer>
    <div class="page-loader" id="page-loader" aria-hidden="true"><div class="page-loader-text">ATARA</div></div>
    <script>
        (function () {
            var loader = document.getElementById('page-loader');
            var MIN_DURATION = 1000;
            var busy = false;
            var show = function () {
                busy = true;
                loader.setAttribute('aria-hidden', 'false');
                loader.classList.add('is-active');
            };
            var hide = function () {
                busy = false;
                loader.classList.remove('is-active');
                loader.setAttribute('aria-hidden', 'true');
            };
            window.addEventListener('pageshow', hide);
            document.addEventListener('click', function (e) {
                var a = e.target.closest && e.target.closest('a[href]');
                if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                if (a.target && a.target !== '_self' || a.hasAttribute('download')) return;
                var url = new URL(a.href, location.href);
                if (url.origin !== location.origin || url.protocol.indexOf('http') !== 0) return;
                if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
                if (busy) { e.preventDefault(); return; }
                e.preventDefault();
                show();
                setTimeout(function () { location.href = a.href; }, MIN_DURATION);
            });
            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (e.defaultPrevented || form.dataset.loaderDone) return;
                if (busy) { e.preventDefault(); return; }
                e.preventDefault();
                show();
                if (e.submitter && e.submitter.name) {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = e.submitter.name;
                    hidden.value = e.submitter.value;
                    form.appendChild(hidden);
                }
                setTimeout(function () {
                    form.dataset.loaderDone = '1';
                    HTMLFormElement.prototype.submit.call(form);
                }, MIN_DURATION);
            });
        })();
    </script>
</body>
</html>
