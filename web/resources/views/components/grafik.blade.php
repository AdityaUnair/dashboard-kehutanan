@props(['id', 'judul', 'ket' => null, 'tinggi' => 'h-56', 'kode' => null, 'ikon' => null, 'jangkar' => null])

{{--
    Kartu grafik: judul, keterangan, kanvas Chart.js, dan tabel data pendamping (diisi otomatis oleh dashboard.js).
    Slot opsional: $aksi (kontrol di kanan judul), $ringkas (satu kalimat temuan di bawah grafik),
    $info (penjelasan lengkap, dibuka lewat tombol ⓘ).
--}}
<figure @if ($jangkar) id="{{ $jangkar }}" @endif {{ $attributes->merge(['class' => 'kartu m-0 flex scroll-mt-28 flex-col']) }}>
    <figcaption class="flex items-start gap-2.5">
        @if ($ikon)
            <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-brand-lembut text-brand-teks"><x-ikon :nama="$ikon" class="size-3.5" /></span>
        @endif
        <div class="min-w-0 flex-1">
            <div class="kartu-judul">@if ($kode)<span class="mr-1 text-ink-3">{{ $kode }}</span>@endif{{ $judul }}</div>
            @if ($ket)
                <div class="kartu-ket">{{ $ket }}</div>
            @endif
        </div>
        {{ $aksi ?? '' }}
        @isset($info)
            <button type="button" class="tombol-info" popovertarget="info-{{ $id }}" aria-label="Penjelasan: {{ $judul }}" title="Kenapa begini?">i</button>
            <div popover id="info-{{ $id }}" class="popover-info">
                <div class="mb-3 flex items-start justify-between gap-4">
                    <div class="kartu-judul">@if ($kode)<span class="mr-1 text-ink-3">{{ $kode }}</span>@endif{{ $judul }}</div>
                    <button type="button" class="text-ink-3 hover:text-ink" popovertarget="info-{{ $id }}" popovertargetaction="hide" aria-label="Tutup">✕</button>
                </div>
                {{ $info }}
            </div>
        @endisset
    </figcaption>
    {{ $slot }}
    <div class="relative {{ $tinggi }}">
        <canvas id="{{ $id }}" role="img" aria-label="{{ $judul }}"></canvas>
    </div>
    @isset($ringkas)
        <p class="mt-2.5 text-[12.5px] leading-snug text-pretty text-ink-2">{{ $ringkas }}</p>
    @endisset
    <details class="lihat-tabel mt-auto pt-2" data-grafik="{{ $id }}">
        <summary>Lihat tabel data</summary>
        <div class="isi"></div>
    </details>
</figure>
