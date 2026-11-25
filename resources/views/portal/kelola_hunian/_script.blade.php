<script>
(function () {
    var today = @json(now()->toDateString());
    var urls = {
        occupy: @json(route('apps.hunian.rooms.occupy', ['room' => '__ID__'])),
        'occupancy-edit': @json(route('apps.hunian.occupancies.update', ['occupancy' => '__ID__'])),
        pay: @json(route('apps.hunian.occupancies.pay', ['occupancy' => '__ID__'])),
        maintenance: @json(route('apps.hunian.rooms.maintenance.start', ['room' => '__ID__'])),
    };
    var units = { daily: 'hari', weekly: 'minggu', monthly: 'bulan', yearly: 'tahun' };
    var d = {
        occupancy: document.getElementById('occupancy-dialog'),
        pay: document.getElementById('pay-dialog'),
        maintenance: document.getElementById('maintenance-dialog'),
    };
    var of = document.getElementById('occupancy-form');
    var pf = document.getElementById('pay-form');
    var mode = document.getElementById('occupancy-mode');
    var modeWrap = document.getElementById('occupancy-mode-wrap');
    var activeFlag = document.getElementById('occupancy-active');
    var method = document.getElementById('occupancy-method');
    var occBox = of.querySelector('[data-period]');
    var payBox = pf.querySelector('[data-period]');
    var $tenant = window.jQuery('#occupancy-tenant');

    // Penghuni lama tanpa tautan tenant ditampilkan memakai nama salinannya (select terkunci).
    function setTenant(p) {
        var value = '';
        $tenant.find('option[data-extra]').remove();
        if (p) {
            value = p.tenant_id ? String(p.tenant_id) : (p.status === 'active' ? '__legacy' : '');
            var exists = $tenant.find('option').filter(function () { return this.value === value; }).length > 0;
            if (value && !exists) $tenant.append(window.jQuery('<option>').attr('data-extra', '1').val(value).text(p.occupant_name));
        }
        $tenant.val(value).trigger('change');
    }

    function url(type, id) { return urls[type].replace('__ID__', id); }
    function parseDate(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function fmt(dt) { return dt.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }); }
    function money(n) { return 'Rp ' + n.toLocaleString('id-ID'); }

    function addMonths(dt, n) {
        var r = new Date(dt.getFullYear(), dt.getMonth() + n, 1);
        var last = new Date(r.getFullYear(), r.getMonth() + 1, 0).getDate();
        r.setDate(Math.min(dt.getDate(), last));
        return r;
    }

    function periodEnd(type, qty, start, endStr) {
        if (type === 'custom') return endStr ? parseDate(endStr) : null;
        if (!(qty > 0)) return null;
        if (type === 'daily') return new Date(start.getFullYear(), start.getMonth(), start.getDate() + qty);
        if (type === 'weekly') return new Date(start.getFullYear(), start.getMonth(), start.getDate() + qty * 7);
        return addMonths(start, type === 'monthly' ? qty : qty * 12);
    }

    // Pratinjau hanya bantuan tampilan; server menghitung ulang tanggal dan nominal.
    function syncPeriod(box) {
        var form = box.closest('form');
        var type = box.querySelector('[name=period_type]').value;
        var qtyInput = box.querySelector('[name=period_qty]');
        var endInput = box.querySelector('[name=period_end]');
        var amount = box.querySelector('[name=amount]');
        var custom = type === 'custom', shown = !box.hidden;
        box.querySelector('[data-qty]').hidden = custom;
        box.querySelector('[data-end]').hidden = !custom;
        if (!custom) box.querySelector('[data-unit]').textContent = units[type];

        var qty = parseInt(qtyInput.value, 10);
        var rent = form.elements.rent_price ? parseInt(form.elements.rent_price.value, 10) || 0 : parseInt(box.dataset.rent, 10) || 0;
        var auto = null;
        if (rent > 0 && qty > 0) auto = type === 'monthly' ? rent * qty : type === 'yearly' ? rent * 12 * qty : null;

        qtyInput.required = shown && !custom;
        endInput.required = shown && custom;
        amount.required = shown && auto === null;
        box.querySelector('[data-amount-hint]').textContent = auto !== null
            ? 'Kosongkan untuk otomatis: ' + money(auto) + '.'
            : 'Wajib diisi untuk rentang ini.';

        var startVal = form.elements[box.dataset.start].value;
        var preview = box.querySelector('[data-preview]');
        if (!startVal) { preview.textContent = 'Isi tanggal mulai untuk melihat periode.'; return; }
        var start = parseDate(startVal), end = periodEnd(type, qty, start, endInput.value);
        preview.textContent = end ? 'Periode ' + fmt(start) + ' → terbayar sampai ' + fmt(end) : '';
    }

    function resetPeriod(box) {
        box.querySelector('[name=period_type]').value = 'monthly';
        box.querySelector('[name=period_qty]').value = 1;
        box.querySelector('[name=period_end]').value = '';
        box.querySelector('[name=amount]').value = '';
        box.querySelector('[name=proof]').value = '';
    }

    [of, pf].forEach(function (form) {
        var box = form.querySelector('[data-period]');
        ['input', 'change'].forEach(function (evt) {
            form.addEventListener(evt, function (e) { if (e.target.name !== 'proof') syncPeriod(box); });
        });
    });

    function syncMode() {
        var editActive = activeFlag.value === '1';
        var isActive = mode.disabled || mode.value === 'active';
        occBox.hidden = !(isActive && !editActive);
        of.querySelector('[data-edit-only]').hidden = !editActive;
        of.check_in_date.required = isActive;
        syncPeriod(occBox);
    }

    function lockMode(locked) {
        activeFlag.value = locked ? '1' : '';
        mode.disabled = locked;
        modeWrap.hidden = locked;
        $tenant.prop('disabled', locked);
        $tenant[0].required = !locked;
        syncMode();
    }

    function openOccupy(p) {
        of.action = url('occupy', p.id);
        method.disabled = true;
        document.getElementById('occupancy-dialog-type').value = 'occupy';
        document.getElementById('occupancy-target').value = p.id;
        document.getElementById('occupancy-title').textContent = (p.mode === 'reserved' ? 'Booking kamar ' : 'Check-in kamar ') + p.room_number;
        of.notes.value = '';
        setTenant(null);
        of.check_in_date.value = p.mode === 'active' ? today : '';
        of.paid_until.value = '';
        of.rent_price.value = p.price;
        resetPeriod(occBox);
        mode.value = p.mode;
        lockMode(false);
        d.occupancy.showModal();
    }

    function openEdit(p) {
        of.action = url('occupancy-edit', p.id);
        method.disabled = false;
        document.getElementById('occupancy-dialog-type').value = 'occupancy-edit';
        document.getElementById('occupancy-target').value = p.id;
        document.getElementById('occupancy-title').textContent = (p.activate ? 'Check-in booking kamar ' : 'Ubah penghuni kamar ') + p.room_number;
        of.notes.value = p.notes || '';
        setTenant(p);
        of.check_in_date.value = p.activate ? today : (p.check_in_date || '');
        of.paid_until.value = p.paid_until || '';
        of.rent_price.value = p.rent_price;
        resetPeriod(occBox);
        mode.value = p.activate ? 'active' : p.status;
        lockMode(p.status === 'active');
        d.occupancy.showModal();
    }

    function openPay(p) {
        pf.action = url('pay', p.id);
        document.getElementById('pay-target').value = p.id;
        document.getElementById('pay-title').textContent = 'Pembayaran kamar ' + p.room_number;
        document.getElementById('pay-info').textContent = p.name + ' · terbayar sampai ' + (p.paid_until_label || 'belum ada tanggal');
        pf.period_start.value = p.paid_until || p.check_in_date || today;
        payBox.dataset.rent = p.rent_price;
        resetPeriod(payBox);
        syncPeriod(payBox);
        d.pay.showModal();
    }

    function openMaintenance(p) {
        var f = document.getElementById('maintenance-form');
        f.action = url('maintenance', p.id);
        document.getElementById('maintenance-target').value = p.id;
        document.getElementById('maintenance-title').textContent = 'Perbaikan kamar ' + p.room_number;
        f.reason.value = '';
        d.maintenance.showModal();
    }

    function bind(attr, fn) {
        document.querySelectorAll('[' + attr + ']').forEach(function (btn) {
            btn.addEventListener('click', function () { fn(JSON.parse(btn.getAttribute(attr))); });
        });
    }
    bind('data-occupy', openOccupy);
    bind('data-edit-occupancy', openEdit);
    bind('data-pay', openPay);
    bind('data-maintenance', openMaintenance);

    document.querySelectorAll('[data-close-dialog]').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('dialog').close(); });
    });
    Object.keys(d).forEach(function (k) {
        d[k].addEventListener('click', function (e) { if (e.target === d[k]) d[k].close(); });
    });
    mode.addEventListener('change', syncMode);

    // Buka kembali dialog beserta isian lama saat validasi gagal (file bukti perlu dipilih ulang).
    @if($errors->any() && old('_target'))
        (function () {
            var type = @json(old('_dialog')), id = @json(old('_target'));
            if (type === 'occupy' || type === 'occupancy-edit') {
                of.action = url(type, id);
                method.disabled = type === 'occupy';
                document.getElementById('occupancy-title').textContent = type === 'occupy' ? 'Penghuni kamar' : 'Ubah penghuni';
                lockMode(activeFlag.value === '1');
                d.occupancy.showModal();
            } else if (type === 'pay') {
                pf.action = url('pay', id);
                syncPeriod(payBox);
                d.pay.showModal();
            } else if (type === 'maintenance') {
                document.getElementById('maintenance-form').action = url('maintenance', id);
                d.maintenance.showModal();
            }
        })();
    @else
        syncMode();
        syncPeriod(payBox);
    @endif

    var filters = document.querySelectorAll('[data-filter]');
    filters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var f = btn.dataset.filter, visible = 0;
            filters.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
            document.querySelectorAll('[data-room]').forEach(function (card) {
                var show = f === 'all' || card.dataset.status === f || (f === 'overdue' && card.dataset.paystate === 'overdue');
                card.hidden = !show;
                if (show) visible++;
            });
            document.querySelectorAll('[data-floor-section]').forEach(function (sec) {
                sec.hidden = !sec.querySelector('[data-room]:not([hidden])');
            });
            document.getElementById('hn-no-match').hidden = visible > 0;
        });
    });
})();
</script>
