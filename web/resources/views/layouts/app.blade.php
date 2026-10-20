<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Dashboard Kehutanan')</title>
    @vite(['resources/css/app.css'])
    @stack('kepala')
</head>
<body class="bg-page font-sans text-ink antialiased">
    @yield('isi')
</body>
</html>
