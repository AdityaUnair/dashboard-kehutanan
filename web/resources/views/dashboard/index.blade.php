@extends('layouts.app')

@section('judul', 'Dashboard Kehutanan Indonesia')

@push('kepala')
    <script>window.DATA = {{ Js::from($grafik) }};</script>
    @vite(['resources/js/dashboard.js'])
@endpush

@section('isi')
@php
    $tabs = [
        'ringkasan' => ['Ringkasan', 'ringkasan'],
        'per-data' => ['Per data', 'grafik'],
        'sebaran' => ['Peta sebaran', 'peta'],
        'gabungan' => ['Analisis gabungan', 'lapisan'],
        'integrasi' => ['Integrasi data', 'basisdata'],
    ];
@endphp

<header class="sticky top-0 z-30 border-b border-garis bg-page/85 backdrop-blur-xl">
    <div class="mx-auto flex max-w-[1360px] items-center gap-4 px-4 py-3 md:px-6">
        <a href="#ringkasan" class="flex shrink-0 items-center gap-2.5" data-ke-tab="ringkasan">
            <span class="grid size-9 place-items-center rounded-xl bg-brand text-[#f6f1e4] shadow-sm"><x-ikon nama="pohon" class="size-[18px]" /></span>
            <span class="hidden text-sm leading-tight font-semibold sm:block">Dashboard Kehutanan<span class="block text-[11px] font-normal text-ink-3">Data-level EAI · Kelompok 3</span></span>
        </a>

        <nav class="mx-auto hidden items-center gap-1 rounded-xl border border-garis bg-surface p-1 lg:flex" role="tablist" aria-label="Halaman dashboard">
            @foreach ($tabs as $id => [$label, $ikon])
                <button class="tab inline-flex items-center gap-1.5" role="tab" data-tab="{{ $id }}" aria-controls="panel-{{ $id }}" aria-selected="false"><x-ikon :nama="$ikon" class="size-4" />{{ $label }}</button>
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-2 lg:ml-0">
            <button id="gantiTema" type="button" class="grid size-9 place-items-center rounded-lg border border-garis bg-surface text-ink-2 hover:text-ink" aria-label="Ganti mode siang/malam" title="Ganti mode siang/malam">
                <x-ikon nama="bulan" class="size-4 dark:hidden" /><x-ikon nama="matahari" class="hidden size-4 dark:block" />
            </button>
            <div class="hidden text-right text-xs leading-tight md:block">
                <div class="font-medium text-ink">{{ auth()->user()->name }}</div>
                <div class="text-ink-3">{{ auth()->user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-garis bg-surface px-3 py-2 text-xs font-medium text-ink-2 hover:text-ink">Keluar</button>
            </form>
        </div>
    </div>

    {{-- Tab versi layar kecil: bisa digeser --}}
    <nav class="flex gap-1 overflow-x-auto px-4 pb-2.5 lg:hidden" role="tablist" aria-label="Halaman dashboard">
        @foreach ($tabs as $id => [$label, $ikon])
            <button class="tab inline-flex items-center gap-1.5" role="tab" data-tab="{{ $id }}" aria-controls="panel-{{ $id }}" aria-selected="false"><x-ikon :nama="$ikon" class="size-4" />{{ $label }}</button>
        @endforeach
    </nav>
</header>

<main class="mx-auto max-w-[1360px] px-4 pt-7 pb-20 md:px-6">
    <section id="panel-ringkasan" role="tabpanel" data-panel="ringkasan">@include('dashboard.partials.ringkasan')</section>
    <section id="panel-per-data" role="tabpanel" data-panel="per-data" hidden>@include('dashboard.partials.per-data')</section>
    <section id="panel-sebaran" role="tabpanel" data-panel="sebaran" hidden>@include('dashboard.partials.sebaran')</section>
    <section id="panel-gabungan" role="tabpanel" data-panel="gabungan" hidden>@include('dashboard.partials.gabungan')</section>
    <section id="panel-integrasi" role="tabpanel" data-panel="integrasi" hidden>@include('dashboard.partials.integrasi')</section>

    <footer class="mt-16 flex flex-wrap items-center justify-between gap-2 border-t border-garis pt-5 text-xs text-ink-3">
        <span>Dibaca dari database <code class="text-ink-2">kehutanan_dashboard</code> (replika) hasil pipeline ETL</span>
    </footer>
</main>
@endsection
