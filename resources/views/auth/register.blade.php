<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar — Library Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
<div class="flex min-h-screen items-center justify-center p-6">
    <div class="w-full max-w-sm rounded-lg bg-white p-8 shadow-nav">
        <h1 class="text-title text-ink">Daftar</h1>
        <p class="mt-1 text-base text-ink-secondary">Buat akun member baru</p>

        <x-errors />

        <form method="POST" action="/register" class="mt-6 flex flex-col gap-4">
            @csrf
            <x-input name="name" label="Nama" />
            <x-input name="username" label="Username" />
            <x-input name="password" label="Password" type="password" :value="''" />
            <x-input name="password_confirmation" label="Konfirmasi Password" type="password" :value="''" />
            <button type="submit"
                    class="h-10 rounded-lg bg-ink px-4 text-base text-white hover:opacity-90">Daftar</button>
        </form>

        <p class="mt-6 text-base text-ink-secondary">Sudah punya akun? <a href="/login" class="text-ink hover:underline">Masuk</a></p>
    </div>
</div>
@vite('resources/js/app.js')
</body>
</html>
