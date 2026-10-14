@props(['heading'])

<section class="border-b border-line py-8">
    <h2 class="mb-8 text-section text-ink">{{ $heading }}</h2>
    <div class="grid grid-cols-3 gap-6">
        {{ $slot }}
    </div>
</section>
