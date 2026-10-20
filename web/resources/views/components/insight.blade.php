@props(['terlihat' => null, 'kenapa' => null, 'lanjut' => null, 'peringatan' => false])

{{-- Pola penjelasan setiap bagian: apa yang terlihat -> kenapa -> sambungan ke bagian berikutnya --}}
<div {{ $attributes->class(['insight mt-4', 'peringatan' => $peringatan]) }}>
    @if ($terlihat)<p><b>Terlihat</b><span>{{ $terlihat }}</span></p>@endif
    @if ($kenapa)<p><b>Kenapa</b><span>{{ $kenapa }}</span></p>@endif
    @if ($lanjut)<p><b>Lanjut</b><span>{{ $lanjut }}</span></p>@endif
    {{ $slot }}
</div>
