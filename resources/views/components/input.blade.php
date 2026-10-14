@props(['name', 'label', 'placeholder' => 'Enter ...', 'type' => 'text', 'value' => old($name)])

<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-base text-ink">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"
           placeholder="{{ $placeholder }}"
           class="h-10 w-full rounded-lg border border-line-input bg-white px-[14px] text-base text-ink shadow-xs placeholder:text-ink-secondary focus:border-[#bdbdbd] focus:outline-none focus:ring-[3px] focus:ring-black/5">
    @error($name)<p class="text-hint text-ink-secondary">{{ $message }}</p>@enderror
</div>
