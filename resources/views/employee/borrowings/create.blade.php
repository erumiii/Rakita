@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Buku', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Peminjaman', 'icon' => '📖', 'active' => true],
    ['href' => '/employee/returns/create', 'label' => 'Pengembalian', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Riwayat', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥'],
];
@endphp

<x-layout title="Peminjaman" subtitle="Catat peminjaman buku oleh member" :nav="$nav">
    <form method="POST" action="{{ route('employee.borrowings.store') }}" class="max-w-3xl">
        @csrf
        <x-section heading="Data Peminjaman">
            <div class="flex flex-col gap-2">
                <label for="member_id" class="text-base text-ink">Member</label>
                <select id="member_id" name="member_id"
                        class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                    <option value="">Pilih member</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected(old('member_id', $selectedMemberId) == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                @error('member_id')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
                @if ($activeCount !== null)
                    <p class="text-hint text-ink-secondary">Pinjaman aktif: {{ $activeCount }} / {{ \App\Services\BorrowingService::MAX_ACTIVE }}</p>
                @endif
            </div>
            <div class="flex flex-col gap-2">
                <label for="book_id" class="text-base text-ink">Buku</label>
                <select id="book_id" name="book_id"
                        class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                    <option value="">Pilih buku</option>
                    @foreach ($books as $book)
                        <option value="{{ $book->id }}" @selected(old('book_id') == $book->id)>{{ $book->title }} — {{ $book->author }}</option>
                    @endforeach
                </select>
                @error('book_id')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-2">
                <label for="borrowed_at" class="text-base text-ink">Waktu Pinjam</label>
                <input id="borrowed_at" name="borrowed_at" type="datetime-local" value="{{ old('borrowed_at', now()->format('Y-m-d\TH:i')) }}"
                       class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                @error('borrowed_at')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-col gap-2">
                <label for="borrow_period_days" class="text-base text-ink">Periode (hari)</label>
                <input id="borrow_period_days" name="borrow_period_days" type="number" min="1" max="7" value="{{ old('borrow_period_days', 7) }}"
                       class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                <p class="text-hint text-ink-secondary">Maksimal 7 hari</p>
                @error('borrow_period_days')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
            </div>
        </x-section>
        <div class="mt-8 flex gap-3">
            <button type="submit" class="h-10 rounded-lg bg-ink px-4 text-base text-white hover:opacity-90">Simpan</button>
            <a href="{{ route('employee.books.index') }}"
               class="inline-flex h-10 items-center rounded-lg border border-line-input bg-white px-4 text-base text-ink">Batal</a>
        </div>
    </form>
</x-layout>
