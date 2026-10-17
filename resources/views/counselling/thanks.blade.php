<x-layouts.app :title="__('Thank you')" solid-header noindex>
    <section class="section pt-36">
        <div class="container-site max-w-xl text-center">
            <x-glyph name="check" class="mx-auto size-12 text-gold-700" />
            <h1 class="mt-4 text-3xl font-extrabold text-ink">
                {{ match ($type) { 'confirmed' => __('Your appointment is confirmed'), 'pending' => __('Your booking has been received'), default => __('Your request has been received') } }}
            </h1>
            <p class="mt-4 text-lg text-muted">
                {{ match ($type) {
                    'confirmed' => __('We have emailed you the details and a link to manage your appointment.'),
                    'pending' => __('Our team will review it and email you a confirmation.'),
                    default => __('A coordinator will contact you confidentially using your preferred method.'),
                } }}
            </p>
            <p class="mt-6 rounded-xl bg-white p-4 text-sm">{{ __('Your reference') }}: <strong class="font-mono text-ink">{{ $reference }}</strong></p>
            <a href="{{ lroute('home') }}" class="btn-dark mt-8">{{ __('Back to the homepage') }}</a>
        </div>
    </section>
</x-layouts.app>
