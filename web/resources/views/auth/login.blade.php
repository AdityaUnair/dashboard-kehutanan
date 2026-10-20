@extends('layouts.app')

@section('judul', 'Masuk · Dashboard Kehutanan')

@section('isi')
<div class="grid min-h-dvh lg:grid-cols-[1.15fr_1fr]">
    <section class="relative hidden overflow-hidden bg-[#6f9a78] text-[#f6f1e4] lg:flex lg:flex-col lg:gap-8 lg:p-12">
        <div class="absolute inset-x-0 bottom-0 h-1/2"><x-lanskap-hutan /></div>
        <div class="relative flex items-center gap-2.5">
            <span class="grid size-9 place-items-center rounded-xl bg-[#1e3a28] text-[#f6f1e4]"><x-ikon nama="pohon" class="size-[18px]" /></span>
            <span class="text-sm font-semibold">Dashboard Kehutanan</span>
        </div>
        <div class="relative">
            <span class="inline-flex rounded-full bg-black/15 px-3 py-1 text-[11.5px] font-medium">Integrasi 5 sumber data · 2018–2025</span>
            <h1 class="mt-5 max-w-lg text-4xl leading-[1.1] font-semibold text-balance text-white">
                Hutan Indonesia: berapa yang tersisa, dan apa yang menggerusnya?
            </h1>
            <p class="mt-4 max-w-md text-[15px] leading-relaxed text-pretty text-[#eef3e6]/90">
                Data BPS dan KLHK dalam tiga format disatukan lewat pipeline ETL ke satu database, lalu dianalisis di sini.
            </p>
        </div>
        <div class="relative grid max-w-lg grid-cols-5 gap-2 text-center">
            @foreach ([['D1', 'Kawasan', 'XLSX', 'perisai'], ['D2', 'Tutupan', 'CSV', 'daun'], ['D3', 'Karhutla', 'XLSX', 'api'], ['D4', 'Kayu', 'JSON', 'kayu'], ['D5', 'Deforestasi', 'CSV', 'kapak']] as [$k, $n, $f, $i])
                <div class="rounded-xl bg-[#1e3a28]/55 px-2 py-3 backdrop-blur-sm">
                    <x-ikon :nama="$i" class="mx-auto size-4 text-[#cfe6c9]" />
                    <div class="mt-1.5 text-[12px] font-medium">{{ $n }}</div>
                    <div class="mt-0.5 text-[10.5px] text-[#d8e4cf]/80">{{ $k }} · {{ $f }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid place-items-center px-6 py-12">
        <form class="w-full max-w-sm" method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                <span class="grid size-9 place-items-center rounded-xl bg-brand text-[#f6f1e4]"><x-ikon nama="pohon" class="size-[18px]" /></span>
                <span class="text-sm font-semibold">Dashboard Kehutanan</span>
            </div>
            <h2 class="text-2xl font-semibold">Masuk</h2>
            <p class="mt-1.5 text-sm text-ink-2">Gunakan akun yang terdaftar untuk membuka dashboard.</p>

            @if ($errors->any())
                <div class="mt-5 rounded-xl border border-peringatan/30 bg-peringatan-lembut px-3.5 py-2.5 text-sm text-peringatan" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <label for="email" class="mt-6 block text-xs font-medium text-ink-2">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1.5 w-full rounded-xl border border-garis bg-surface-2 px-3.5 py-2.5 text-sm text-ink outline-none placeholder:text-ink-3 focus:border-brand focus:ring-4 focus:ring-brand/15">

            <label for="password" class="mt-4 block text-xs font-medium text-ink-2">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1.5 w-full rounded-xl border border-garis bg-surface-2 px-3.5 py-2.5 text-sm text-ink outline-none focus:border-brand focus:ring-4 focus:ring-brand/15">

            <label class="mt-4 flex items-center gap-2 text-sm text-ink-2">
                <input type="checkbox" name="ingat" value="1" class="size-4 rounded accent-[var(--brand)]"> Ingat saya
            </label>

            <button type="submit" class="mt-6 w-full rounded-xl bg-brand py-2.5 text-sm font-semibold text-white transition hover:brightness-110">
                Masuk
            </button>
        </form>
    </section>
</div>
@endsection
