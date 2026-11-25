<nav class="hn-tabs" aria-label="Menu Kelola Hunian">
    <a href="{{ route('apps.hunian') }}" @class(['is-active' => request()->routeIs('apps.hunian')])>Monitoring</a>
    <a href="{{ route('apps.hunian.payments') }}" @class(['is-active' => request()->routeIs('apps.hunian.payments*')])>Verifikasi bayar @if(($pendingPayments = \App\Models\HunianPayment::where('status', 'pending')->count()) > 0)<span class="hn-tab-count">{{ $pendingPayments }}</span>@endif</a>
    <a href="{{ route('apps.hunian.master') }}" @class(['is-active' => request()->routeIs('apps.hunian.master*')])>Master kamar</a>
    <a href="{{ route('apps.hunian.tenants') }}" @class(['is-active' => request()->routeIs('apps.hunian.tenants*')])>Informasi penghuni</a>
    <a href="{{ route('apps.hunian.tickets') }}" @class(['is-active' => request()->routeIs('apps.hunian.tickets*')])>Keluhan @if(($unreadTickets = \App\Models\HunianTicket::where('manager_unread', true)->count()) > 0)<span class="hn-tab-count">{{ $unreadTickets }}</span>@endif</a>
    <a href="{{ route('apps.hunian.residents') }}" @class(['is-active' => request()->routeIs('apps.hunian.residents')])>Log penghuni</a>
    <a href="{{ route('apps.hunian.logs') }}" @class(['is-active' => request()->routeIs('apps.hunian.logs')])>Log aktivitas</a>
</nav>
