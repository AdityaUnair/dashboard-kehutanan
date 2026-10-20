@extends('layouts.app')

@section('judul', 'Masuk · Dashboard Kehutanan')

@section('isi')
<div class="login-wrap">
    <section class="login-hero">
        <div>
            <div class="eyebrow" style="color:#9fd0ae">Kelompok 3 · Integrasi Aplikasi Korporasi</div>
            <h1>Hutan Indonesia 2018–2025: berapa yang tersisa, dan apa yang menggerusnya?</h1>
            <p>Dashboard ini menyatukan lima sumber data kehutanan dari BPS dan KLHK melalui pipeline ETL ke satu database terpusat.</p>
        </div>
        <ul>
            <li>Kawasan hutan · BPS (XLSX)</li>
            <li>Tutupan lahan hutan · BPS/KLHK (CSV)</li>
            <li>Kebakaran hutan &amp; lahan · SiPongi KLHK (XLSX)</li>
            <li>Deforestasi netto · BPS/KLHK (CSV)</li>
        </ul>
    </section>

    <section class="login-form">
        <form class="login-box" method="POST" action="{{ url('/login') }}">
            @csrf
            <h2>Masuk</h2>
            <p class="sub">Gunakan akun yang terdaftar untuk membuka dashboard.</p>

            @if ($errors->any())
                <div class="galat">{{ $errors->first() }}</div>
            @endif

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">

            <label class="ingat"><input type="checkbox" name="ingat" value="1"> Ingat saya</label>

            <button class="btn-utama" type="submit">Masuk</button>
        </form>
    </section>
</div>
@endsection
