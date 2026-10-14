@php
$nav = [
    ['href' => '/employee/books', 'label' => 'Books', 'icon' => '📚'],
    ['href' => '/employee/borrowings/create', 'label' => 'Borrow Book', 'icon' => '📖'],
    ['href' => '/employee/returns/create', 'label' => 'Return Book', 'icon' => '↩️'],
    ['href' => '/employee/borrowings/history', 'label' => 'Borrow History', 'icon' => '🕘'],
    ['href' => '/employee/members', 'label' => 'Members', 'icon' => '👥', 'active' => true],
];
@endphp

<x-layout title="Edit Member" subtitle="Ubah akun anggota" :nav="$nav">
    <form method="POST" action="{{ route('employee.members.update', $member) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        <x-section heading="Member information">
            <x-input name="name" label="Name" :value="$member->name" />
            <div class="flex flex-col gap-2">
                <label for="password" class="text-base text-ink">New Password</label>
                <input id="password" name="password" type="password" value=""
                       placeholder="Leave blank to keep current password"
                       class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs placeholder:text-ink-secondary focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
                <p class="text-hint text-ink-secondary">Kosongkan jika tidak ingin mengganti password. Minimal 8 karakter.</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input id="is_active" name="is_active" type="checkbox" value="1"
                       class="size-4 rounded border-line-input"
                       @checked(old('is_active', $member->is_active))>
                <label for="is_active" class="text-base text-ink">Status aktif</label>
                @error('is_active')<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
            </div>
        </x-section>
        <div class="mt-8 flex gap-3">
            <button type="submit" class="h-10 rounded-lg bg-brand px-4 text-base text-white hover:opacity-90">Save</button>
            <a href="{{ route('employee.members.index') }}"
               class="inline-flex h-10 items-center rounded-lg border border-line-input px-4 text-base text-ink">Cancel</a>
        </div>
    </form>
</x-layout>
