@props(['title', 'icon' => 'info'])
<div class="rounded-(--radius-card) border border-dashed border-ink/15 bg-white/60 px-6 py-14 text-center">
    <x-glyph :name="$icon" class="mx-auto size-10 text-gold-700" />
    <p class="mt-4 text-lg font-bold text-ink">{{ $title }}</p>
    <div class="mt-2 text-muted">{{ $slot }}</div>
</div>
