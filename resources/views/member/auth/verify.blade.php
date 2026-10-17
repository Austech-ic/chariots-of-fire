<x-auth-card :title="__('Verify your email')" :intro="__('We have sent a verification link to your email address. Please click it to activate your account.')">
    <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn-dark w-full">{{ __('Resend verification email') }}</button></form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="btn-outline w-full">{{ __('Sign out') }}</button></form>
</x-auth-card>
