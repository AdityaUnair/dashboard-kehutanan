@extends('layouts.app')

@section('judul', 'Dashboard Kehutanan Indonesia')

@section('isi')
<div class="layout">
    <aside class="side">
        <div class="brand">Dashboard Kehutanan<small>Data-level EAI · Kelompok 3</small></div>
        <nav class="nav">
            <a href="#ringkasan">Ringkasan</a>
            <span class="grup">Per data</span>
            <a href="#kawasan">1 · Kawasan hutan</a>
            <a href="#tutupan">2 · Tutupan hutan</a>
            <a href="#deforestasi">3 · Deforestasi</a>
            <a href="#karhutla">4 · Kebakaran</a>
            <a href="#kayu">5 · Produksi kayu</a>
            <span class="grup">Gabungan</span>
            <a href="#tren-gabungan">Tren gabungan</a>
            <a href="#kawasan-vs-tutupan">Kawasan vs tutupan</a>
            <a href="#benang-merah">Benang merah provinsi</a>
            <a href="#validasi">Validasi silang</a>
            <span class="grup">Sistem</span>
            <a href="#integrasi">Status integrasi</a>
        </nav>
        <div class="side-user">
            Masuk sebagai
            <strong>{{ auth()->user()->name }}</strong>
            {{ auth()->user()->email }}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-keluar" type="submit">Keluar</button>
            </form>
        </div>
    </aside>

    <main class="main">
        @include('dashboard.partials.ringkasan')
        @include('dashboard.partials.kawasan')
        @include('dashboard.partials.tutupan')
        @include('dashboard.partials.deforestasi')
        @include('dashboard.partials.karhutla')
        @include('dashboard.partials.kayu')
        @include('dashboard.partials.gabungan')
        @include('dashboard.partials.integrasi')

        <footer class="footer">
            Semua angka dibaca dari database <code>kehutanan_dashboard</code> (replika) hasil pipeline ETL.
        </footer>
    </main>
</div>
@endsection

@push('skrip')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        window.DATA = {{ Js::from($grafik) }};
    </script>
    <script src="{{ asset('js/dashboard.js') }}?v={{ filemtime(public_path('js/dashboard.js')) }}"></script>
@endpush
