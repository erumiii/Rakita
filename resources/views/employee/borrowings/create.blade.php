@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Borrow Book', 'icon' => '📖', 'active' => true],
    ['href' => '/employee/returns/create', 'label' => 'Return Book', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow History', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Borrow Book" subtitle="Catat peminjaman buku oleh member" :nav="$nav">
    <form method="POST" action="{{ route('employee.borrowings.store') }}" class="max-w-3xl">
        @csrf
        @if (session('success'))
            <p class="mb-4 rounded-lg border border-line-input bg-white px-4 py-2 text-base text-ink">{{ session('success') }}</p>
        @endif
        <x-section heading="Borrow book">
            <div class="flex flex-col gap-2">
                <label for="member_id" class="text-base text-ink">Member</label>
                <select id="member_id" name="member_id"
                        class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                    <option value="">Select member</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected(old('member_id', $selectedMemberId) == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                @error('member_id')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                @if ($activeCount !== null)
                    <p class="text-hint text-ink-secondary">Active borrows: {{ $activeCount }} / {{ \App\Services\BorrowingService::MAX_ACTIVE }}</p>
                @endif
            </div>
            <div class="flex flex-col gap-2">
                <label for="book_id" class="text-base text-ink">Book</label>
                <select id="book_id" name="book_id"
                        class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                    <option value="">Select book</option>
                    @foreach ($books as $book)
                        <option value="{{ $book->id }}" @selected(old('book_id') == $book->id)>{{ $book->title }} — {{ $book->author }}</option>
                    @endforeach
                </select>
                @error('book_id')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-2">
                <label for="borrowed_at" class="text-base text-ink">Borrowed at</label>
                <input id="borrowed_at" name="borrowed_at" type="datetime-local" value="{{ old('borrowed_at', now()->format('Y-m-d\TH:i')) }}"
                       class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                @error('borrowed_at')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-2">
                <label for="borrow_period_days" class="text-base text-base-ink">Period (days)</label>
                <input id="borrow_period_days" name="borrow_period_days" type="number" min="1" max="7" value="{{ old('borrow_period_days', 7) }}"
                       class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                <p class="text-hint text-ink-secondary">Maksimal 7 hari</p>
                @error('borrow_period_days')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
            </div>
        </x-section>
        <div class="mt-8 flex gap-3">
            <button type="submit" class="h-10 rounded-lg bg-brand px-4 text-base text-white hover:opacity-90">Save</button>
            <a href="{{ route('employee.books.index') }}"
               class="inline-flex h-10 items-center rounded-lg border border-line-input px-4 text-base text-ink">Cancel</a>
        </div>
    </form>
</x-layout>
