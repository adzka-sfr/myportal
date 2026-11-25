{{-- Dialog operasional: penghuni/booking, pembayaran, perbaikan --}}
<dialog class="hn-dialog" id="occupancy-dialog">
    <form method="POST" id="occupancy-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_method" value="PUT" id="occupancy-method" disabled>
        <input type="hidden" name="_dialog" id="occupancy-dialog-type" value="{{ old('_dialog') }}">
        <input type="hidden" name="_target" id="occupancy-target" value="{{ old('_target') }}">
        <input type="hidden" name="_active" id="occupancy-active" value="{{ old('_active') }}">
        <div><div class="eyebrow">Penghuni</div><h2 id="occupancy-title">Penghuni kamar</h2></div>
        @if(in_array(old('_dialog'), ['occupy', 'occupancy-edit'], true) && $errors->any())
            <ul class="hn-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <div class="form-grid">
            <label class="span-2" id="occupancy-mode-wrap">Jenis
                <select name="mode" id="occupancy-mode">
                    <option value="active" @selected(old('mode', 'active') === 'active')>Check-in (menghuni sekarang)</option>
                    <option value="reserved" @selected(old('mode') === 'reserved')>Booking (belum masuk)</option>
                </select>
            </label>
            <label class="span-2">Penghuni
                <select name="tenant_id" id="occupancy-tenant" class="js-select2" data-placeholder="Cari nama penghuni…" required>
                    <option value=""></option>
                    @php($oldTenant = old('tenant_id'))
                    @if($oldTenant && ! $tenantOptions->contains('id', (int) $oldTenant) && ($extra = \App\Models\HunianTenant::find($oldTenant)))
                        <option value="{{ $extra->id }}" selected>{{ $extra->full_name }} — {{ $extra->phone }}</option>
                    @endif
                    @foreach($tenantOptions as $option)
                        <option value="{{ $option->id }}" @selected((int) $oldTenant === $option->id)>{{ $option->full_name }} — {{ $option->phone }}</option>
                    @endforeach
                </select>
                <span class="hn-hint">Hanya penghuni aktif yang belum menempati kamar. <a href="{{ route('apps.hunian.tenants') }}">Kelola informasi penghuni</a></span>
            </label>
            <label>Tanggal masuk<input type="date" name="check_in_date" value="{{ old('check_in_date') }}"></label>
            <label data-edit-only>Sewa terbayar sampai<input type="date" name="paid_until" value="{{ old('paid_until') }}"></label>
            <label class="span-2">Harga sewa / bulan (Rp)<input type="number" name="rent_price" min="0" step="1000" value="{{ old('rent_price') }}"><span class="hn-hint">Default mengikuti harga di master kamar. Dipakai untuk nominal otomatis periode bulanan/tahunan.</span></label>
            @include('portal.kelola_hunian._period', ['start' => 'check_in_date'])
            <label class="span-2">Catatan<textarea name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea></label>
        </div>
        <div class="hn-dialog-actions">
            <button class="button button-quiet" type="button" data-close-dialog>Batal</button>
            <button class="button button-primary" type="submit">Simpan <span aria-hidden="true">→</span></button>
        </div>
    </form>
</dialog>

<dialog class="hn-dialog" id="pay-dialog">
    <form method="POST" id="pay-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_dialog" value="pay">
        <input type="hidden" name="_target" id="pay-target" value="{{ old('_target') }}">
        <div><div class="eyebrow">Pembayaran sewa</div><h2 id="pay-title">Catat pembayaran</h2></div>
        @if(old('_dialog') === 'pay' && $errors->any())
            <ul class="hn-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <p class="hn-hint" id="pay-info"></p>
        <div class="form-grid">
            <label class="span-2">Mulai dari tanggal<input type="date" name="period_start" required value="{{ old('period_start') }}"></label>
            @include('portal.kelola_hunian._period', ['start' => 'period_start'])
        </div>
        <p class="hn-hint">Pembayaran baru dihitung sebagai pemasukan setelah dicocokkan di halaman Verifikasi bayar.</p>
        <div class="hn-dialog-actions">
            <button class="button button-quiet" type="button" data-close-dialog>Batal</button>
            <button class="button button-primary" type="submit">Catat pembayaran <span aria-hidden="true">→</span></button>
        </div>
    </form>
</dialog>

<dialog class="hn-dialog" id="maintenance-dialog">
    <form method="POST" id="maintenance-form">
        @csrf
        <input type="hidden" name="_dialog" value="maintenance">
        <input type="hidden" name="_target" id="maintenance-target" value="{{ old('_target') }}">
        <div><div class="eyebrow">Perbaikan</div><h2 id="maintenance-title">Mulai perbaikan</h2></div>
        @if(old('_dialog') === 'maintenance' && $errors->any())
            <ul class="hn-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
        <label>Keterangan<textarea name="reason" rows="3" maxlength="255" required>{{ old('reason') }}</textarea></label>
        <div class="hn-dialog-actions">
            <button class="button button-quiet" type="button" data-close-dialog>Batal</button>
            <button class="button button-primary" type="submit">Mulai perbaikan</button>
        </div>
    </form>
</dialog>
