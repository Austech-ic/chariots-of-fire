<x-layouts.app :title="__('Unsubscribe')" noindex>
    <section class="section pt-36">
        <div class="container-site max-w-xl text-center">
            <h1 class="text-3xl font-extrabold text-fg">{{ __('Unsubscribe from the newsletter?') }}</h1>
            <p class="mt-4 text-lg text-muted">{{ __('You will stop receiving ministry newsletters at this address.') }}</p>
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-8">
                @csrf
                <button class="btn-dark">{{ __('Yes, unsubscribe me') }}</button>
            </form>
        </div>
    </section>
</x-layouts.app>
