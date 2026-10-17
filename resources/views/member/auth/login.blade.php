<x-auth-card :title="__('Member sign in')">
    <form method="POST" action="{{ route('login') }}" class="space-y-5" data-submit-once>
        @csrf
        <x-field name="email" type="email" :label="__('Email')" required autocomplete="username" autofocus />
        <x-field name="password" type="password" :label="__('Password')" required autocomplete="current-password" />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" value="1" class="accent-gold-700"> {{ __('Remember me') }}</label>
            <a href="{{ route('password.request') }}" class="font-semibold text-gold-700 underline">{{ __('Forgot password?') }}</a>
        </div>
        <button class="btn-dark w-full">{{ __('Sign in') }}</button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">{{ __('New here?') }} <a href="{{ route('register') }}" class="font-semibold text-gold-700 underline">{{ __('Create a member account') }}</a></p>
</x-auth-card>
