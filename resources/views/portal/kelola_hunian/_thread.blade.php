{{-- Percakapan keluhan. Variabel: $ticket, $mine (manager|tenant), $imageRoute, $replyUrl --}}
<div class="hn-chat" id="hn-chat">
    @foreach($ticket->messages as $message)
        <div @class(['hn-msg', 'is-mine' => $message->sender === $mine])>
            <small>{{ $message->sender === 'manager' ? 'Pengelola' : ($message->sender_name ?? 'Penghuni') }} · {{ $message->created_at->format('d M Y H:i') }}</small>
            @if($message->body)<p>{{ $message->body }}</p>@endif
            @if($message->image_path)
                <a href="{{ route($imageRoute, $message) }}" target="_blank" rel="noopener"><img loading="lazy" src="{{ route($imageRoute, $message) }}" alt="Foto lampiran"></a>
            @endif
        </div>
    @endforeach
</div>

<form method="POST" action="{{ $replyUrl }}" enctype="multipart/form-data" class="hn-reply">
    @csrf
    <textarea name="body" rows="3" maxlength="2000" placeholder="Tulis pesan…">{{ old('body') }}</textarea>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between">
        <label class="hn-field" style="font-weight:500">Lampirkan foto (opsional)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
        <button class="button button-primary" type="submit">Kirim <span aria-hidden="true">→</span></button>
    </div>
</form>
<script>
(function () { var c = document.getElementById('hn-chat'); if (c) c.scrollTop = c.scrollHeight; })();
</script>
