<x-layouts.app :title="__('Donation status')" solid-header noindex>
    <section class="section pt-36">
        <div class="container-site max-w-xl text-center">
            @if ($donation->isPaid())
                <x-glyph name="heart" class="mx-auto size-12 text-gold-700" />
                <h1 class="mt-4 text-3xl font-extrabold text-ink">{{ __('Thank you for your gift') }}</h1>
                <p class="mt-4 text-lg text-muted">{{ __('Your donation of :amount has been confirmed.', ['amount' => $donation->formattedAmount()]) }}</p>
                @if ($donation->donor_email)<p class="mt-2 text-muted">{{ __('A receipt is on its way to your email.') }}</p>@endif
            @elseif ($donation->status === 'pending')
                <x-glyph name="clock" class="mx-auto size-12 text-gold-700" />
                <h1 class="mt-4 text-3xl font-extrabold text-ink">{{ __('We are confirming your payment') }}</h1>
                <p class="mt-4 text-lg text-muted">{{ __('This can take a few minutes. You will receive a receipt by email once the payment provider confirms it.') }}</p>
                <a href="{{ request()->url() }}" class="btn-outline mt-6">{{ __('Check again') }}</a>
            @else
                <x-glyph name="info" class="mx-auto size-12 text-ember" />
                <h1 class="mt-4 text-3xl font-extrabold text-ink">{{ __('The payment was not completed') }}</h1>
                <p class="mt-4 text-lg text-muted">{{ __('No money has been recorded as received. You are welcome to try again.') }}</p>
                <a href="{{ lroute('give') }}" class="btn-dark mt-6">{{ __('Try again') }}</a>
            @endif
            <p class="mt-8 rounded-xl bg-white p-4 text-sm">{{ __('Reference') }}: <strong class="font-mono text-ink">{{ $donation->reference }}</strong></p>
        </div>
    </section>
</x-layouts.app>
