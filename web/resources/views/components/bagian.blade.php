@props(['id', 'nomor', 'judul', 'sumber', 'sub' => null])

<section id="{{ $id }}" {{ $attributes->merge(['class' => 'mt-12 first:mt-0 scroll-mt-24']) }}>
    <div class="mb-4 flex flex-wrap items-center gap-2.5">
        <span class="grid h-6 min-w-6 place-items-center rounded-md bg-brand-lembut px-1.5 text-[12px] font-semibold text-brand-teks">{{ $nomor }}</span>
        <h2 class="text-lg font-semibold tracking-tight md:text-xl">{{ $judul }}</h2>
        <span class="lencana ml-auto">{{ $sumber }}</span>
    </div>
    @if ($sub)
        <p class="-mt-1 mb-5 max-w-3xl text-sm leading-relaxed text-ink-2">{{ $sub }}</p>
    @endif
    {{ $slot }}
</section>
