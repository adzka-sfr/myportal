<dialog class="hn-dialog" id="room-dialog">
    <form method="POST" id="room-form" action="{{ route('apps.hunian.master.rooms.store') }}">
        @csrf
        <input type="hidden" name="_method" value="PUT" id="room-method" disabled>
        <input type="hidden" name="_dialog" value="room">
        <input type="hidden" name="_edit_id" id="room-edit-id" value="{{ old('_edit_id') }}">
        <div><div class="eyebrow">Master kamar</div><h2 id="room-title">Tambah kamar</h2></div>
        @if(old('_dialog') === 'room' && $errors->any())
            <ul class="hn-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif

        <div class="form-grid">
            <label>Nomor / kode kamar<input name="room_number" maxlength="20" required value="{{ old('room_number') }}" placeholder="Mis. 101"></label>
            <label>Lantai<input type="number" name="floor" min="0" max="99" required value="{{ old('floor', 1) }}"></label>
            <label>Tipe kamar<input name="room_type" maxlength="60" value="{{ old('room_type') }}" placeholder="Mis. Standar, Deluxe"></label>
            <label>Harga sewa / bulan (Rp)<input type="number" name="monthly_price" min="0" step="1000" required value="{{ old('monthly_price', 0) }}"></label>
        </div>

        <fieldset>
            <legend>Fasilitas</legend>
            @if($facilities->isEmpty())
                <span class="hn-hint">Belum ada master fasilitas. Tambahkan dulu di panel Fasilitas.</span>
            @else
                <div class="hn-checks">
                    @foreach($facilities as $facility)
                        <label><input type="checkbox" name="facilities[]" value="{{ $facility->id }}" @checked(in_array($facility->id, array_map('intval', old('facilities', []))))> {{ $facility->name }}</label>
                    @endforeach
                </div>
            @endif
        </fieldset>

        <label>Catatan<textarea name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea></label>

        <div class="hn-dialog-actions">
            <button class="button button-quiet" type="button" data-close-dialog>Batal</button>
            <button class="button button-primary" type="submit">Simpan <span aria-hidden="true">→</span></button>
        </div>
    </form>
</dialog>
