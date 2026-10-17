<x-layouts.app :title="__('Newsletter')">
    <x-page-hero :eyebrow="__('Stay connected')" :title="__('Ministry newsletter')" :intro="__('Programme announcements, new messages and stories from the field — delivered to your inbox.')" />
    <section class="section">
        <div class="container-site max-w-xl">
            @if (session('newsletter_status'))
                <div role="status" class="mb-6 rounded-xl bg-emerald-50 p-4 text-emerald-900">{{ session('newsletter_status') }}</div>
            @endif
            <x-flash />
            <form method="POST" action="{{ lroute('newsletter.subscribe') }}" class="card relative space-y-5 p-6 sm:p-8" data-submit-once>
                @csrf <x-honeypot />
                <x-field name="name" :label="__('Name (optional)')" autocomplete="name" />
                <x-field name="email" type="email" :label="__('Email address')" required autocomplete="email" />
                <label class="flex items-start gap-3 text-sm text-body">
                    <input type="checkbox" name="consent" value="1" required class="mt-1 size-4 accent-gold-700" @checked(old('consent'))>
                    <span>{{ __('I agree to receive ministry news by email. I will confirm by clicking a link we send, and I can unsubscribe at any time.') }}</span>
                </label>
                <button class="btn-dark">{{ __('Subscribe') }}</button>
            </form>
        </div>
    </section>
</x-layouts.app>
