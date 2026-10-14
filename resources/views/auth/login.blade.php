<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rakita</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
<div class="flex min-h-screen flex-col">
    <div class="flex flex-1 flex-col bg-white">
        <header class="flex h-16 items-center gap-3 border-b border-line px-8">
            <div class="flex size-7 items-center justify-center rounded-lg bg-brand"><x-icon name="check" class="size-4 text-white" /></div>
            <span class="text-lg font-medium">Rakita</span>
        </header>

        <div class="flex flex-1 items-center justify-center px-6">
            <div class="w-full max-w-sm">
            <h1 class="text-3xl font-semibold text-ink">Sign In</h1>
            <p class="mt-2 text-base text-ink-secondary">Sign in to your library account</p>

            <x-errors />

            <form method="POST" action="/login" class="mt-8 flex flex-col gap-4">
                @csrf
                <x-input name="username" label="Username" placeholder="e.g. johndoe" />
                <x-input name="password" label="Password" type="password" placeholder="Enter your password" :value="''" />
                <button type="submit"
                        class="h-10 rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Sign In</button>
            </form>

            <p class="mt-8 text-center text-base text-ink-secondary">Don't have an account?
                <a href="/register" class="text-ink hover:underline">Create an account</a>
            </p>
            </div>
        </div>
    </div>
</div>
@vite('resources/js/app.js')
</body>
</html>
