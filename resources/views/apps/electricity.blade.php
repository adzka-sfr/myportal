@extends('layouts.app', ['title' => 'Kalkulator Listrik'])

@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('dashboard') }}">← Kembali ke portal</a><div class="eyebrow">Perkiraan energi</div><h1>Kalkulator Listrik</h1><p class="muted">Masukkan daya, durasi pemakaian, dan tarif listrik untuk mendapat estimasi biaya.</p></div><span class="app-icon app-icon-electricity large-icon">⚡</span></div>
@if(auth()->user()->canUseFeature('electricity', 'basic'))
<section class="tool-grid">
    <div class="panel tool-panel"><div class="panel-heading"><div><h2>Satu alat</h2><p>Estimasi konsumsi bulanan sebuah alat.</p></div><span class="step-number">01</span></div>
        <form method="POST" action="{{ route('apps.electricity.calculate') }}" class="stack-form">@csrf
            <div class="form-grid"><label>Daya alat <span class="unit">W</span><input type="number" name="watts" step="any" min="0.1" value="{{ old('watts', 100) }}" required></label><label>Jam pemakaian per hari <span class="unit">jam</span><input type="number" name="hours_per_day" step="any" min="0" max="24" value="{{ old('hours_per_day', 8) }}" required></label><label>Hari pemakaian per bulan <span class="unit">hari</span><input type="number" name="days_per_month" min="1" max="31" value="{{ old('days_per_month', 30) }}" required></label><label>Tarif listrik <span class="unit">Rp/kWh</span><input type="number" name="tariff" step="any" min="0" value="{{ old('tariff', 1444.70) }}" required></label></div>
            <button class="button button-primary" type="submit">Hitung estimasi <span aria-hidden="true">→</span></button>
        </form>
        @if(session('result'))<div class="result-card"><span class="result-label">Estimasi bulanan</span><strong>Rp {{ number_format(session('result.cost'), 0, ',', '.') }}</strong><div class="result-meta">{{ number_format(session('result.energy'), 2, ',', '.') }} kWh per bulan</div></div>@endif
    </div>
    @if(auth()->user()->canUseFeature('electricity', 'breakdown'))
    <div class="panel tool-panel"><div class="panel-heading"><div><h2>Beberapa alat</h2><p>Gabungkan pemakaian sejumlah alat.</p></div><span class="step-number">02</span></div>
        <form method="POST" action="{{ route('apps.electricity.breakdown') }}" class="stack-form" id="appliance-form">@csrf
            <label>Tarif listrik <span class="unit">Rp/kWh</span><input type="number" name="tariff" step="any" min="0" value="{{ old('tariff', 1444.70) }}" required></label>
            <div id="appliance-rows" class="appliance-rows">
                @foreach(old('appliances', [['name' => 'Kulkas', 'watts' => 100, 'hours' => 24, 'days' => 30], ['name' => 'Lampu', 'watts' => 10, 'hours' => 6, 'days' => 30]]) as $i => $item)
                <div class="appliance-row"><label>Nama<input name="appliances[{{ $i }}][name]" value="{{ $item['name'] }}" maxlength="80" required></label><label>Daya (W)<input type="number" name="appliances[{{ $i }}][watts]" step="any" min="0.1" value="{{ $item['watts'] }}" required></label><label>Jam/hari<input type="number" name="appliances[{{ $i }}][hours]" step="any" min="0" max="24" value="{{ $item['hours'] }}" required></label><label>Hari/bulan<input type="number" name="appliances[{{ $i }}][days]" min="1" max="31" value="{{ $item['days'] }}" required></label></div>
                @endforeach
            </div>
            <div class="form-inline"><button class="button button-quiet button-small" type="button" id="add-appliance">＋ Tambah alat</button><button class="button button-primary" type="submit">Hitung semua</button></div>
        </form>
        @if(session('breakdown'))<div class="result-card"><span class="result-label">Total estimasi bulanan</span><strong>Rp {{ number_format(session('breakdown.cost'), 0, ',', '.') }}</strong><div class="result-meta">{{ number_format(session('breakdown.energy'), 2, ',', '.') }} kWh per bulan</div><ul class="result-list">@foreach(session('breakdown.items') as $item)<li><span>{{ $item['name'] }} <small>{{ number_format($item['kwh'], 2, ',', '.') }} kWh</small></span><b>Rp {{ number_format($item['cost'], 0, ',', '.') }}</b></li>@endforeach</ul></div>@endif
    </div>
    @endif
</section>
<p class="formula-note">Rumus: daya (W) ÷ 1.000 × jam pemakaian × hari × tarif per kWh. Hasil adalah estimasi.</p>
@else<div class="empty-state"><h3>Fitur belum diberikan</h3><p>Hubungi Superadmin untuk meminta izin menggunakan kalkulator ini.</p></div>@endif
@if(auth()->user()->canUseFeature('electricity', 'breakdown'))
<script>
document.getElementById('add-appliance')?.addEventListener('click', () => {
    const rows = document.getElementById('appliance-rows');
    if (rows.children.length >= 30) return;
    const i = rows.children.length;
    const row = document.createElement('div'); row.className = 'appliance-row';
    [['name','Nama','text',''],['watts','Daya (W)','number','0.1'],['hours','Jam/hari','number','0'],['days','Hari/bulan','number','1']].forEach(([key,label,type,min]) => {
        const wrap=document.createElement('label'); wrap.append(document.createTextNode(label));
        const input=document.createElement('input'); input.name=`appliances[${i}][${key}]`; input.type=type; input.required=true;
        if(type==='number'){input.min=min;input.step='any';input.value=key==='days'?'30':'';} input.max=key==='hours'?'24':key==='days'?'31':'1000000'; wrap.append(input); row.append(wrap);
    }); rows.append(row);
});
</script>
@endif
@endsection
