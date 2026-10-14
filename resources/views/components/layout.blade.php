@props(['title', 'subtitle' => null, 'nav' => []])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Library Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
</head>
<body class="bg-canvas font-sans text-ink antialiased">
<div class="m-[22px] flex min-h-[calc(100vh-44px)] gap-0 rounded-lg bg-white shadow-nav">
    <aside class="w-[256px] shrink-0 bg-sidebar flex flex-col">
        <div class="flex h-16 items-center gap-3 border-b border-line-sidebar px-[22px]">
            <div class="flex size-7 items-center justify-center rounded-lg bg-brand text-white">✓</div>
            <span class="text-base">Library Management System</span>
        </div>
        <nav class="flex flex-1 flex-col gap-1 p-3">
            <p class="px-4 pb-1 pt-3 text-group text-ink-muted">Menu</p>
            @foreach($nav as $item)
                <x-nav-item :href="$item['href']" :icon="$item['icon']" :active="$item['active'] ?? false">
                    {{ $item['label'] }}
                </x-nav-item>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="p-3">
            @csrf
            <x-nav-item as="button" icon="⎋">Logout</x-nav-item>
        </form>
    </aside>
    <div class="flex flex-1 flex-col">
        <header class="h-16 border-b border-line"></header>
        <main class="flex-1 px-[68px] py-8">
            <h1 class="text-title text-ink">{{ $title }}</h1>
            @if($subtitle)<p class="mt-1 text-base text-ink-secondary">{{ $subtitle }}</p>@endif
            <hr class="my-6 border-line">
            @if (session('success'))
                <p class="mb-6 rounded-lg border border-line-input bg-sidebar px-4 py-3 text-base text-ink">{{ session('success') }}</p>
            @endif
            <x-errors />
            {{ $slot }}
        </main>
        </main>
    </div>
</div>
@vite('resources/js/app.js')
</body>
</html>
