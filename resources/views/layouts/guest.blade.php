<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'JHSOFTERP' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <main class="auth-shell">
        <a href="{{ route('login') }}" class="brand auth-brand">
            <span class="brand-mark">J</span>
            <span>JHSOFT<span class="brand-light">ERP</span></span>
        </a>
        <section class="auth-card">
            {{ $slot }}
        </section>
        <p class="auth-caption">Sistem manajemen operasional JHSOFTERP</p>
    </main>
</body>
</html>
