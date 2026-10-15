@php
$nav = [
    ['href' => '/member/books', 'label' => 'Book Collection', 'icon' => 'books', 'active' => true],
    ['href' => '/member/borrowings', 'label' => 'My Borrowings', 'icon' => 'book-open'],
    ['href' => '/member/reservations', 'label' => 'My Reservations', 'icon' => 'clock'],
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

    <div class="mt-8 flex items-center gap-4">
        @if ($myReservation)
            <x-badge status="pending" />
            <a href="{{ route('member.reservations.index') }}" class="text-base text-ink-secondary hover:text-ink">View my reservations</a>
        @elseif (! $myActiveBorrow)
            <form method="POST" action="{{ route('member.reservations.store') }}">
                @csrf
                <input type="hidden" name="book_id" value="{{ $book->id }}">
                <button type="submit"
                        class="inline-flex h-10 items-center rounded-lg bg-ink px-4 text-base text-white shadow-nav transition-[background-color,border-color,box-shadow,opacity] duration-200 ease-out hover:opacity-90">Reserve this book</button>
            </form>
        @endif
    </div>

    <a href="{{ route('member.books.index') }}" class="mt-8 inline-block text-base text-ink-secondary hover:text-ink">← Back to collection</a>
</x-layout>
