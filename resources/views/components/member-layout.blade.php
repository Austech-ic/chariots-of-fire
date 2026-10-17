@props(['title'])
@php
    $links = [
        ['member.dashboard', __('Overview'), 'user'],
        ['member.appointments', __('Appointments'), 'calendar'],
        ['member.registrations', __('Event registrations'), 'users'],
        ['member.donations', __('Giving history'), 'heart'],
        ['member.resources', __('Member resources'), 'book-open'],
        ['member.profile', __('Profile & security'), 'lock'],
        ['member.preferences', __('Communication preferences'), 'mail'],
        ['member.account', __('Account requests'), 'info'],
    ];
@endphp
<x-layouts.app :title="$title" solid-header noindex>
    <section class="pb-20 pt-28 sm:pt-32">
        <div class="container-site grid gap-8 lg:grid-cols-12">
            <aside class="lg:col-span-3">
                <div class="card p-5">
                    <p class="text-sm text-muted">{{ __('Signed in as') }}</p>
                    <p class="truncate font-semibold text-ink">{{ auth()->user()->name }}</p>
                    <nav class="mt-4 space-y-1" aria-label="{{ __('Member portal') }}">
                        @foreach ($links as [$route, $label, $icon])
                            <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm {{ request()->routeIs($route.'*') ? 'bg-ink text-white' : 'text-ink hover:bg-ivory' }}" @if (request()->routeIs($route)) aria-current="page" @endif>
                                <x-glyph :name="$icon" class="size-4" /> {{ $label }}
                            </a>
                        @endforeach
                        @if (auth()->user()->is_staff)
                            <a href="{{ url('/admin') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-gold-700 hover:bg-ivory"><x-glyph name="arrow-right" class="size-4" /> {{ __('Staff CMS') }}</a>
                        @endif
                    </nav>
                    <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-ink/10 pt-4">
                        @csrf
                        <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-ink hover:bg-ivory"><x-glyph name="logout" class="size-4" /> {{ __('Sign out') }}</button>
                    </form>
                </div>
            </aside>
            <div class="lg:col-span-9">
                <h1 class="text-3xl font-extrabold text-ink">{{ $title }}</h1>
                <div class="mt-6"><x-flash />{{ $slot }}</div>
            </div>
        </div>
    </section>
</x-layouts.app>
