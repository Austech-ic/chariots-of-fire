<x-layouts.app :title="$sermon->tr('title')" solid-header noindex>
    <section class="section pt-36">
        <div class="container-site max-w-xl text-center">
            <x-glyph name="lock" class="mx-auto size-12 text-gold-700" />
            <h1 class="mt-4 text-3xl font-extrabold text-ink">{{ $sermon->tr('title') }}</h1>
            <p class="mt-4 text-lg text-muted">{{ __('This message is available to registered members. Please sign in or create an account to continue.') }}</p>
            <div class="mt-8 flex justify-center gap-3">
                <a href="{{ route('login') }}" class="btn-dark">{{ __('Sign in') }}</a>
                <a href="{{ route('register') }}" class="btn-outline">{{ __('Create an account') }}</a>
            </div>
        </div>
    </section>
</x-layouts.app>
