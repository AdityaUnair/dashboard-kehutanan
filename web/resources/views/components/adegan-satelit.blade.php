{{--
    Adegan scroll pembuka analisis gabungan: pohon tumbuh -> kamera naik -> bumi dilihat dari satelit.
    Posisi scroll diubah jadi angka 0..1 oleh dashboard.js (aturAdegan) lalu dipakai untuk transform/opacity.
--}}
@php
    // Deretan pohon: posisi & tinggi deterministik supaya gambarnya selalu sama
    $pohon = [];
    foreach ([[520, 70, 26], [585, 95, 34], [650, 125, 44]] as $i => [$dasar, $tinggi, $jarak]) {
        for ($x = 10 + $i * 9; $x < 1200; $x += $jarak) {
            $pohon[] = ['x' => $x, 'y' => $dasar, 't' => $tinggi * (0.75 + 0.25 * abs(sin($x * 0.37 + $i))), 'lapis' => $i];
        }
    }
    $warnaLapis = ['#5b8a62', '#3f6f4a', '#2a5236'];
@endphp

<div id="adeganSatelit" class="relative mx-[calc(50%-50vw)] mt-16" style="height: 320vh" aria-label="Animasi: dari hutan ke citra satelit">
    <div class="sticky top-16 h-[calc(100dvh-4rem)] overflow-hidden bg-[#d7e6cf]">
        <svg viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" class="absolute inset-0 h-full w-full" aria-hidden="true">
            {{-- angkasa (muncul saat kamera naik) --}}
            <g data-adegan="angkasa" style="opacity: 0">
                <rect width="1200" height="700" fill="#0f1d2b"/>
                @for ($i = 0; $i < 70; $i++)
                    <circle cx="{{ (int) (abs(sin($i * 12.9898)) * 1200) }}" cy="{{ (int) (abs(sin($i * 78.233)) * 700) }}" r="{{ $i % 7 === 0 ? 1.6 : 0.9 }}" fill="#e8efe2" opacity="{{ 0.4 + ($i % 5) * 0.12 }}"/>
                @endfor
            </g>

            {{-- bumi dilihat dari satelit; peta Indonesia diisi dashboard.js --}}
            <g data-adegan="bumi" style="opacity: 0; transform-box: view-box; transform-origin: 600px 350px">
                <circle cx="600" cy="350" r="270" fill="#2c5d7c"/>
                <circle cx="600" cy="350" r="270" fill="none" stroke="#9cc3d8" stroke-opacity="0.35" stroke-width="6"/>
                <g data-adegan="indonesia"></g>
                <rect data-adegan="pemindai" x="330" y="80" width="6" height="540" fill="#bfe3c4" opacity="0.8" style="opacity: 0"/>
            </g>

            {{-- lanskap hutan dari dekat --}}
            <g data-adegan="lanskap" style="transform-box: view-box; transform-origin: 600px 700px">
                <circle cx="980" cy="140" r="46" fill="#f3d9a4" opacity="0.8"/>
                <path d="M0 470 C180 420 320 450 470 425 C640 398 760 445 930 420 C1060 402 1140 425 1200 410 V700 H0Z" fill="#9cbf96"/>
                @foreach ($pohon as $p)
                    @php $w = $p['t'] * 0.42; $y = $p['y']; $t = $p['t']; $x = $p['x']; @endphp
                    <path data-pohon="{{ $loop->index }}" fill="{{ $warnaLapis[$p['lapis']] }}"
                          style="transform-box: fill-box; transform-origin: 50% 100%; transform: scaleY(0)"
                          d="M{{ $x }} {{ $y - $t }} L{{ $x - $w / 2 }} {{ $y - $t * 0.38 }} L{{ $x - $w * 0.3 }} {{ $y - $t * 0.38 }} L{{ $x - $w * 0.62 }} {{ $y }} L{{ $x + $w * 0.62 }} {{ $y }} L{{ $x + $w * 0.3 }} {{ $y - $t * 0.38 }} L{{ $x + $w / 2 }} {{ $y - $t * 0.38 }} Z"/>
                @endforeach
                <rect x="0" y="648" width="1200" height="60" fill="#2a5236"/>
            </g>
        </svg>

        {{-- teks besar di tengah atas; memudar & bergeser mengikuti scroll (gaya halaman produk) --}}
        @foreach ([
            ['Dari dekat, hutan adalah pohon.', 'Tumbuh satu per satu. Tapi bagaimana mengukurnya untuk seluruh Indonesia?', 'text-[#1e3a28]', 'text-[#2f5a3a]'],
            ['Naik ke langit.', 'Ada dua cara melihat hutan: kawasan yang ditetapkan di atas peta, dan tutupan yang dipotret satelit.', 'text-white', 'text-[#d6e6d2]'],
            ['Dari satelit, terlihat yang sebenarnya.', 'Hijau = persentase daratan berhutan tiap provinsi (D2). Di bawah, kita bandingkan dengan kawasan hutan.', 'text-white', 'text-[#d6e6d2]'],
        ] as $i => [$judul, $isi, $warnaJudul, $warnaIsi])
            <div data-keterangan="{{ $i }}" class="pointer-events-none absolute inset-x-0 top-[8%] mx-auto max-w-3xl px-6 text-center" style="opacity: {{ $i === 0 ? 1 : 0 }}">
                <h3 class="text-3xl leading-tight font-semibold text-balance md:text-5xl {{ $warnaJudul }}">{{ $judul }}</h3>
                <p class="mx-auto mt-4 max-w-xl text-[15px] leading-relaxed text-pretty md:text-lg {{ $warnaIsi }}">{{ $isi }}</p>
            </div>
        @endforeach
    </div>
</div>
