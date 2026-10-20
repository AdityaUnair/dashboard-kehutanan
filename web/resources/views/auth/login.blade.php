@extends('layouts.app')

@section('judul', 'Masuk · Dashboard Kehutanan')

@section('isi')
<div class="grid min-h-screen md:grid-cols-[1.1fr_1fr]">
    <section class="flex flex-col justify-between gap-10 bg-brand-gelap p-8 text-white md:p-14">
        <div>
            <p class="text-[11px] font-semibold tracking-[0.18em] text-white/60 uppercase">Kelompok 3 · Integrasi Aplikasi Korporasi</p>
            <h1 class="mt-4 max-w-md text-3xl leading-tight font-bold tracking-tight md:text-4xl">
                Hutan Indonesia 2018–2025: berapa yang tersisa, dan apa yang menggerusnya?
            </h1>
            <p class="mt-4 max-w-md text-[15px] text-white/70">
                Lima sumber data kehutanan dari BPS dan KLHK disatukan lewat pipeline ETL ke satu database terpusat.
            </p>
        </div>
        <dl class="grid max-w-md gap-2 text-[13px]">
            @foreach ([
                ['D1', 'Kawasan hutan', 'BPS · XLSX'],
                ['D2', 'Tutupan lahan hutan', 'BPS/KLHK · CSV'],
                ['D3', 'Kebakaran hutan & lahan', 'SiPongi · XLSX'],
                ['D5', 'Deforestasi netto', 'BPS/KLHK · CSV'],
            ] as [$kode, $nama, $sumber])
                <div class="flex items-center gap-3 border-t border-white/10 pt-2">
                    <dt class="w-7 font-semibold text-white/50">{{ $kode }}</dt>
                    <dd class="flex-1 text-white/90">{{ $nama }}</dd>
                    <dd class="text-white/50">{{ $sumber }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="grid place-items-center px-6 py-12">
        <form class="w-full max-w-sm" method="POST" action="{{ url('/login') }}">
            @csrf
            <h2 class="text-2xl font-bold tracking-tight">Masuk</h2>
            <p class="mt-1 text-sm text-ink-2">Gunakan akun yang terdaftar untuk membuka dashboard.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-lg border border-turun/30 bg-turun/5 px-3 py-2.5 text-sm text-[#a33433]" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <label for="email" class="mt-6 block text-xs font-semibold text-ink-2">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1.5 w-full rounded-lg border border-black/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">

            <label for="password" class="mt-4 block text-xs font-semibold text-ink-2">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1.5 w-full rounded-lg border border-black/15 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">

            <label class="mt-4 flex items-center gap-2 text-sm text-ink-2">
                <input type="checkbox" name="ingat" value="1" class="size-4 rounded accent-brand"> Ingat saya
            </label>

            <button type="submit" class="mt-6 w-full rounded-lg bg-brand py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-gelap">
                Masuk
            </button>
        </form>
    </section>
</div>
@endsection
