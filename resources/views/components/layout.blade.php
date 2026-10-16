@props(['title', 'subtitle' => null, 'nav' => []])

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
<body class="bg-sidebar font-sans text-ink antialiased">
<div class="flex h-screen overflow-hidden bg-white">
    <aside class="w-[256px] shrink-0 bg-sidebar flex flex-col border-r border-line-sidebar">
        <div class="flex h-16 items-center gap-3 border-b border-line-sidebar px-[22px]">
            <div class="flex size-7 items-center justify-center rounded-lg bg-brand"><x-icon name="check" class="size-4 text-white" /></div>
            <span class="text-lg font-medium">Rakita</span>
        </div>
        <nav class="flex flex-1 flex-col gap-1 p-3">
            <p class="px-4 pb-1 pt-3 text-group text-ink-muted">Menu</p>
            @foreach($nav as $item)
                <x-nav-item :href="$item['href']" :icon="$item['icon']" :active="$item['active'] ?? false">
                    {{ $item['label'] }}
                </x-nav-item>
            @endforeach
        </nav>
        <div class="p-3 mb-5">
            @if (auth()->user()->role === 'member')
                <p class="truncate px-4 pb-2 text-lg font-bold text-ink-secondary">{{ auth()->user()->name }}</p>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-nav-item as="button" icon="logout" danger>Logout</x-nav-item>
            </form>
        </div>
    </aside>
    <div class="flex h-screen flex-1 flex-col overflow-y-auto">
        <header class="flex h-16 items-center justify-end border-b border-line px-[68px]">
            <x-notification-bell />
        </header>
        <main class="flex-1 px-[68px] py-8">
            <h1 class="text-title text-ink">{{ $title }}</h1>
            @if($subtitle)<p class="mt-1 text-base text-ink-secondary">{{ $subtitle }}</p>@endif
            <hr class="my-6 border-line">
            @if (session('success'))
                <x-alert />
            @endif
            <x-errors />
            {{ $slot }}
        </main>
    </div>
</div>
<x-confirm />
@vite('resources/js/app.js')
</body>
</html>
