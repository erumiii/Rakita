@php
$nav = [
    ['href' => '/member/books', 'label' => 'Book Collection', 'icon' => 'books', 'active' => true],
    ['href' => '/member/borrowings', 'label' => 'My Borrowings', 'icon' => 'book-open'],
];
@endphp

<x-layout title="{{ $book->title }}" subtitle="Book detail" :nav="$nav">
    <div class="grid grid-cols-3 gap-6">
        <div class="flex flex-col gap-2">
            <p class="text-hint text-ink-secondary">Title</p>
            <p class="text-base text-ink">{{ $book->title }}</p>
        </div>
        <div class="flex flex-col gap-2">
            <p class="text-hint text-ink-secondary">Author</p>
            <p class="text-base text-ink">{{ $book->author }}</p>
        </div>
        <div class="flex flex-col gap-2">
            <p class="text-hint text-ink-secondary">Publisher</p>
            <p class="text-base text-ink">{{ $book->publisher }}</p>
        </div>
        <div class="flex flex-col gap-2">
            <p class="text-hint text-ink-secondary">Pages</p>
            <p class="text-base text-ink">{{ $book->page }}</p>
        </div>
        <div class="flex flex-col gap-2">
            <p class="text-hint text-ink-secondary">ISBN</p>
            <p class="text-base text-ink">{{ $book->isbn }}</p>
        </div>
        <div class="flex flex-col gap-2">
            <p class="text-hint text-ink-secondary">Status</p>
            <p><x-badge :status="$book->status" /></p>
        </div>
    </div>

    <a href="{{ route('member.books.index') }}" class="mt-8 inline-block text-base text-ink-secondary hover:text-ink">← Back to collection</a>
</x-layout>
