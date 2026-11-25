<fieldset class="hn-period" data-period data-start="{{ $start }}">
    <legend>Periode sewa &amp; pembayaran</legend>
    <div class="form-grid">
        <label>Rentang
            <select name="period_type">
                @foreach(\App\Models\HunianPayment::PERIODS as $value => $label)
                    <option value="{{ $value }}" @selected(old('period_type', 'monthly') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label data-qty>Jumlah <span data-unit>bulan</span><input type="number" name="period_qty" min="1" max="365" value="{{ old('period_qty', 1) }}"></label>
        <label data-end hidden>Tanggal keluar<input type="date" name="period_end" value="{{ old('period_end') }}"></label>
        <label class="span-2">Nominal dibayar (Rp)<input type="number" name="amount" min="0" step="500" value="{{ old('amount') }}"><span class="hn-hint" data-amount-hint></span></label>
        <label class="span-2">Bukti pembayaran (opsional)<input type="file" name="proof" accept="image/jpeg,image/png,image/webp"><span class="hn-hint">JPG, PNG, atau WebP, maksimal 4 MB.</span></label>
    </div>
    <p class="hn-preview" data-preview></p>
</fieldset>
