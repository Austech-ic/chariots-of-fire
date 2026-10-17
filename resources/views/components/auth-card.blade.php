@props(['title', 'intro' => null])
<x-layouts.app :title="$title" solid-header noindex>
    <section class="flex min-h-screen items-center pb-16 pt-28">
        <div class="container-site max-w-md">
            <div class="card p-8">
                <h1 class="text-2xl font-extrabold text-ink">{{ $title }}</h1>
                @if ($intro)<p class="mt-2 text-muted">{{ $intro }}</p>@endif
                <div class="mt-6"><x-flash />{{ $slot }}</div>
            </div>
        </div>
    </section>
</x-layouts.app>
