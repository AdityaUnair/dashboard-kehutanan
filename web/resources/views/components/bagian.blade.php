@props(['id', 'nomor', 'judul', 'sumber', 'sub' => null])

<section id="{{ $id }}" class="mt-14 scroll-mt-6">
    <div class="flex flex-wrap items-center gap-3">
        <span class="nomor-bagian">{{ $nomor }}</span>
        <h2 class="text-xl font-bold tracking-tight md:text-[22px]">{{ $judul }}</h2>
        <span class="label-sumber">{{ $sumber }}</span>
    </div>
    @if ($sub)
        <p class="mt-1.5 mb-5 max-w-3xl text-sm text-ink-2">{{ $sub }}</p>
    @endif
    {{ $slot }}
</section>
