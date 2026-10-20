@props(['judul', 'sub' => null])

<div {{ $attributes->merge(['class' => 'mb-4 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="max-w-3xl">
        <h1 class="text-2xl font-semibold text-balance md:text-[28px]">{{ $judul }}</h1>
        @if ($sub)
            <p class="mt-1.5 text-[14px] leading-relaxed text-pretty text-ink-2">{{ $sub }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
