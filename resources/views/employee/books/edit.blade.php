@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => '📚', 'active' => true],
    ['href' => '/employee/borrowings/create', 'label' => 'Borrow Book', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Return Book', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow History', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Edit Book" subtitle="Ubah data buku" :nav="$nav">
    <form method="POST" action="{{ route('employee.books.update', $book) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        <x-section heading="Book information">
            <x-input name="title" label="Title" :value="$book->title" />
            <x-input name="author" label="Author" :value="$book->author" />
            <x-input name="publisher" label="Publisher" :value="$book->publisher" />
            <x-input name="page" label="Page" type="number" :value="$book->page" />
            <x-input name="isbn" label="ISBN" :value="$book->isbn" />
        </x-section>
        <div class="mt-8 flex gap-3">
            <button type="submit" class="h-10 rounded-lg bg-brand px-4 text-base text-white hover:opacity-90">Save</button>
            <a href="{{ route('employee.books.index') }}"
               class="inline-flex h-10 items-center rounded-lg border border-line-input px-4 text-base text-ink">Cancel</a>
        </div>
    </form>
</x-layout>
