@extends('layouts.app')

@section('judul', 'Dashboard Kehutanan Indonesia')

@push('kepala')
    <script>window.DATA = {{ Js::from($grafik) }};</script>
    @vite(['resources/js/dashboard.js'])
@endpush

@section('isi')
<div class="lg:grid lg:grid-cols-[248px_minmax(0,1fr)]">
    <aside class="bg-brand-gelap px-4 py-6 text-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
        <div class="px-3">
            <div class="text-[17px] font-bold">Dashboard Kehutanan</div>
            <div class="mt-1 text-[10.5px] font-semibold tracking-[0.15em] text-white/50 uppercase">Data-level EAI · Kelompok 3</div>
        </div>

        <nav class="mt-6 grid grid-cols-2 gap-0.5 lg:grid-cols-1" aria-label="Navigasi bagian">
            @php
                $menu = [
                    null => [['ringkasan', 'Ringkasan']],
                    'Per data' => [['kawasan', '1 · Kawasan hutan'], ['tutupan', '2 · Tutupan hutan'], ['deforestasi', '3 · Deforestasi'],
                                   ['karhutla', '4 · Kebakaran'], ['kayu', '5 · Produksi kayu']],
                    'Gabungan' => [['tren-gabungan', 'Tren gabungan'], ['kawasan-vs-tutupan', 'Kawasan vs tutupan'],
                                   ['benang-merah', 'Benang merah'], ['validasi', 'Validasi silang']],
                    'Sistem' => [['integrasi', 'Status integrasi']],
                ];
            @endphp
            @foreach ($menu as $grup => $tautan)
                @if ($grup)
                    <div class="col-span-full mt-4 mb-1 px-3 text-[10px] font-semibold tracking-[0.15em] text-white/40 uppercase">{{ $grup }}</div>
                @endif
                @foreach ($tautan as [$jangkar, $label])
                    <a href="#{{ $jangkar }}" class="nav-tautan" data-jangkar="{{ $jangkar }}">{{ $label }}</a>
                @endforeach
            @endforeach
        </nav>

        <div class="mt-6 border-t border-white/10 px-3 pt-4 text-xs text-white/60 lg:mt-auto">
            Masuk sebagai
            <div class="mt-0.5 text-[13px] font-semibold text-white">{{ auth()->user()->name }}</div>
            <div>{{ auth()->user()->email }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="mt-3 w-full rounded-lg border border-white/20 py-2 text-[13px] text-white transition-colors hover:bg-white/10">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="mx-auto w-full max-w-[1320px] px-4 pt-8 pb-16 md:px-8">
        @include('dashboard.partials.ringkasan')
        @include('dashboard.partials.kawasan')
        @include('dashboard.partials.tutupan')
        @include('dashboard.partials.deforestasi')
        @include('dashboard.partials.karhutla')
        @include('dashboard.partials.kayu')
        @include('dashboard.partials.gabungan')
        @include('dashboard.partials.integrasi')

        <footer class="mt-16 border-t border-grid pt-4 text-xs text-ink-3">
            <code>kehutanan_dashboard</code> (replika) hasil pipeline ETL.
        </footer>
    </main>
</div>
@endsection
