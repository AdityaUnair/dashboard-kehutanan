@props(['id', 'nomor', 'judul', 'sumber', 'sub' => null, 'ikon' => null])

<section id="{{ $id }}" {{ $attributes->merge(['class' => 'mt-8 first:mt-0 scroll-mt-24']) }}>
    <div class="mb-3 flex flex-wrap items-center gap-3">
        @if ($ikon)
            <span class="grid size-9 place-items-center rounded-xl bg-brand-lembut text-brand-teks"><x-ikon :nama="$ikon" class="size-[18px]" /></span>
        @endif
        <div>
            <div class="text-[11px] font-semibold text-ink-3 uppercase">Bagian {{ $nomor }}</div>
            <h2 class="text-lg font-semibold text-balance">{{ $judul }}</h2>
        </div>
        <span class="lencana ml-auto">{{ $sumber }}</span>
    </div>
    @if ($sub)
        <p class="mb-3 max-w-4xl text-[13px] leading-relaxed text-pretty text-ink-2">{{ $sub }}</p>
    @endif
    {{ $slot }}
</section>
