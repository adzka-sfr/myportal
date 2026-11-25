<nav class="hn-tabs" aria-label="Menu Penyewa Hunian">
    <a href="{{ route('apps.penyewa') }}" @class(['is-active' => request()->routeIs('apps.penyewa')])>Beranda</a>
    <a href="{{ route('apps.penyewa.payments') }}" @class(['is-active' => request()->routeIs('apps.penyewa.payments')])>Pembayaran</a>
    <a href="{{ route('apps.penyewa.tickets') }}" @class(['is-active' => request()->routeIs('apps.penyewa.tickets*')])>Keluhan @if(($unreadReplies = $tenant->tickets()->where('tenant_unread', true)->count()) > 0)<span class="hn-tab-count">{{ $unreadReplies }}</span>@endif</a>
    <a href="{{ route('apps.penyewa.profile') }}" @class(['is-active' => request()->routeIs('apps.penyewa.profile')])>Profil</a>
</nav>
