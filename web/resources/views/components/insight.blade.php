@props(['terlihat' => null, 'kenapa' => null, 'lanjut' => null, 'peringatan' => false])

{{-- Pola penjelasan setiap bagian: apa yang terlihat -> kenapa -> sambungan ke bagian berikutnya --}}
<div {{ $attributes->class(['insight', 'peringatan' => $peringatan]) }}>
    @if ($terlihat)<p><b>Terlihat</b>{{ $terlihat }}</p>@endif
    @if ($kenapa)<p><b>Kenapa</b>{{ $kenapa }}</p>@endif
    @if ($lanjut)<p><b>Lanjut</b>{{ $lanjut }}</p>@endif
    {{ $slot }}
</div>
