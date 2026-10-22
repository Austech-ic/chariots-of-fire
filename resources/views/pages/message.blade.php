<x-layouts.app :title="$title" noindex>
    <section class="section pt-36">
        <div class="container-site max-w-xl text-center">
            <x-glyph name="check" class="mx-auto size-12 text-accent" />
            <h1 class="mt-4 text-3xl font-extrabold text-fg">{{ $title }}</h1>
            <p class="mt-4 text-lg text-muted">{{ $message }}</p>
            <a href="{{ lroute('home') }}" class="btn-dark mt-8">{{ __('Back to the homepage') }}</a>
        </div>
    </section>
</x-layouts.app>
