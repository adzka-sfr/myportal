<nav class="hn-tabs" aria-label="Menu Kelola Hunian">
    <a href="{{ route('apps.hunian') }}" @class(['is-active' => request()->routeIs('apps.hunian')])>Monitoring</a>
    <a href="{{ route('apps.hunian.master') }}" @class(['is-active' => request()->routeIs('apps.hunian.master*')])>Master kamar</a>
    <a href="{{ route('apps.hunian.residents') }}" @class(['is-active' => request()->routeIs('apps.hunian.residents')])>Log penghuni</a>
    <a href="{{ route('apps.hunian.logs') }}" @class(['is-active' => request()->routeIs('apps.hunian.logs')])>Log aktivitas</a>
</nav>
