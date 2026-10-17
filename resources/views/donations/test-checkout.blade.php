<x-layouts.app :title="__('Test checkout')" solid-header noindex>
    <section class="section pt-36">
        <div class="container-site max-w-md">
            <div class="card p-8 text-center">
                <span class="badge bg-gold text-ink">{{ __('TEST MODE — no real money') }}</span>
                <h1 class="mt-4 text-2xl font-extrabold text-ink">{{ __('Simulated payment provider') }}</h1>
                <p class="mt-2 text-sm text-muted font-mono">{{ $reference }}</p>
                <div class="mt-6 grid gap-3">
                    <form method="POST" action="{{ route('give.test-settle', $reference) }}">@csrf<input type="hidden" name="outcome" value="success"><button class="btn-dark w-full">{{ __('Simulate successful payment') }}</button></form>
                    <form method="POST" action="{{ route('give.test-settle', $reference) }}">@csrf<input type="hidden" name="outcome" value="failed"><button class="btn-outline w-full">{{ __('Simulate failed payment') }}</button></form>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
