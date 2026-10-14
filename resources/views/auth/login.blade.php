<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Library Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
<div class="flex min-h-screen items-center justify-center p-6">
    <div class="w-full max-w-sm rounded-lg bg-white p-8 shadow-nav">
        <h1 class="text-title text-ink">Masuk</h1>
        <p class="mt-1 text-base text-ink-secondary">Masuk ke akun perpustakaan Anda</p>

        <x-errors />

        <form method="POST" action="/login" class="mt-6 flex flex-col gap-4">
            @csrf
            <x-input name="username" label="Username" />
            <x-input name="password" label="Password" type="password" :value="''" />
            <button type="submit"
                    class="h-10 rounded-lg bg-ink px-4 text-base text-white hover:opacity-90">Masuk</button>
        </form>

        <p class="mt-6 text-base text-ink-secondary">Belum punya akun? <a href="/register" class="text-ink hover:underline">Daftar</a></p>
    </div>
</div>
@vite('resources/js/app.js')
</body>
</html>
