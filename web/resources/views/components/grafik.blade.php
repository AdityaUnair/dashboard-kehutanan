@props(['id', 'judul', 'ket' => null, 'tinggi' => 'h-80'])

{{-- Kartu grafik: judul, keterangan, kanvas Chart.js, dan tabel data pendamping (diisi otomatis oleh dashboard.js) --}}
<figure {{ $attributes->merge(['class' => 'kartu m-0']) }}>
    <figcaption>
        <div class="kartu-judul">{{ $judul }}</div>
        @if ($ket)
            <div class="kartu-ket">{{ $ket }}</div>
        @endif
    </figcaption>
    {{ $slot }}
    <div class="relative {{ $tinggi }}">
        <canvas id="{{ $id }}" role="img" aria-label="{{ $judul }}"></canvas>
    </div>
    <details class="lihat-tabel" data-grafik="{{ $id }}">
        <summary>Lihat tabel data</summary>
        <div class="isi"></div>
    </details>
</figure>
