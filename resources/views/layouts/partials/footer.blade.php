@php
    $social = array_filter([
        'youtube' => setting('social_youtube'), 'facebook' => setting('social_facebook'),
        'instagram' => setting('social_instagram'), 'x-social' => setting('social_x'),
    ]);
@endphp
<footer class="bg-ink text-white/80 dark:border-t dark:border-white/10">
    <div class="container-site grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-1">
            <p class="font-display text-lg font-extrabold text-white">{{ setting('site_name') }}</p>
            <p class="mt-3 text-sm">{{ setting('footer_text') ?? setting('tagline') }}</p>
            @if ($social)
                <div class="mt-5 flex gap-2">
                    @foreach ($social as $icon => $url)
                        <a href="{{ $url }}" class="rounded-full bg-white/5 p-2.5 hover:bg-gold hover:text-ink" rel="noopener" target="_blank" aria-label="{{ ucfirst(str_replace('-social', '', $icon)) }}"><x-glyph :name="$icon" class="size-4" /></a>
                    @endforeach
                </div>
            @endif
        </div>
        <nav aria-label="{{ __('Footer') }}">
            <p class="eyebrow-light">{{ __('Explore') }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ lroute('about') }}">{{ __('About us') }}</a></li>
                <li><a class="hover:text-white" href="{{ lroute('sermons.index') }}">{{ __('Sermons') }}</a></li>
                <li><a class="hover:text-white" href="{{ lroute('live') }}">{{ __('Live streaming') }}</a></li>
                <li><a class="hover:text-white" href="{{ lroute('events.index') }}">{{ __('Programmes') }}</a></li>
                <li><a class="hover:text-white" href="{{ lroute('gallery.index') }}">{{ __('Gallery') }}</a></li>
            </ul>
        </nav>
        <div>
            <p class="eyebrow-light">{{ __('Connect') }}</p>
            <ul class="mt-4 space-y-2 text-sm">
                <li><a class="hover:text-white" href="{{ lroute('counselling.index') }}">{{ __('Counselling') }}</a></li>
                <li><a class="hover:text-white" href="{{ lroute('contact') }}">{{ __('Contact us') }}</a></li>
                <li><a class="hover:text-white" href="{{ lroute('give') }}">{{ __('Give online') }}</a></li>
                <li><a class="hover:text-white" href="{{ route('login') }}">{{ __('Member portal') }}</a></li>
                @foreach ($footerPages as $footerPage)
                    <li><a class="hover:text-white" href="{{ lroute('pages.show', $footerPage) }}">{{ $footerPage->tr('title') }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <p class="eyebrow-light">{{ __('Contact') }}</p>
            <ul class="mt-4 space-y-3 text-sm">
                @if (setting('contact_address'))<li class="flex gap-2"><x-glyph name="map-pin" class="mt-0.5 size-4 shrink-0 text-gold" /> <span>{{ setting('contact_address') }}</span></li>@endif
                @if (setting('contact_phone'))<li class="flex gap-2"><x-glyph name="phone" class="mt-0.5 size-4 shrink-0 text-gold" /> <a class="hover:text-white" href="tel:{{ preg_replace('/[^0-9+]/', '', setting('contact_phone')) }}">{{ setting('contact_phone') }}</a></li>@endif
                @if (setting('contact_email'))<li class="flex gap-2"><x-glyph name="mail" class="mt-0.5 size-4 shrink-0 text-gold" /> <a class="hover:text-white" href="mailto:{{ setting('contact_email') }}">{{ setting('contact_email') }}</a></li>@endif
            </ul>
            <form method="POST" action="{{ lroute('newsletter.subscribe') }}" class="mt-6">
                @csrf <x-honeypot />
                <label for="footer-newsletter" class="text-sm font-semibold text-white">{{ __('Newsletter') }}</label>
                <div class="mt-2 flex gap-2">
                    <input id="footer-newsletter" type="email" name="email" required autocomplete="email" placeholder="{{ __('Your email') }}" class="min-w-0 flex-1 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm text-white placeholder:text-white/40 focus:border-gold focus:outline-none">
                    <button class="btn-primary btn-sm">{{ __('Join') }}</button>
                </div>
                <label class="mt-2 flex items-start gap-2 text-xs text-white/60"><input type="checkbox" name="consent" value="1" required class="mt-0.5"> {{ __('I agree to receive ministry news by email. I can unsubscribe at any time.') }}</label>
                @if (session('newsletter_status'))<p class="mt-2 text-xs text-gold" role="status">{{ session('newsletter_status') }}</p>@endif
            </form>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-site flex flex-col gap-2 py-6 text-xs text-white/50 sm:flex-row sm:justify-between">
            <p>© {{ now()->year }} {{ setting('site_name') }}. {{ __('All rights reserved.') }}</p>
            <p>{{ __('Times shown in West Africa Time (Lagos).') }}</p>
        </div>
    </div>
</footer>
