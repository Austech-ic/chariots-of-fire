<?php

namespace App\Http\Controllers;

use App\Jobs\SyncNewsletterSubscription;
use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmation;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Double opt-in: a subscription becomes active only after email confirmation. */
class NewsletterController extends Controller
{
    public function show(): View
    {
        return view('pages.newsletter');
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'name' => ['nullable', 'string', 'max:120'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ]);

        $email = NewsletterSubscription::normalize($data['email']);
        $subscription = NewsletterSubscription::firstOrNew(['email_normalized' => $email]);

        // Same response whether or not the address exists, to avoid revealing subscribers.
        if ($subscription->status !== 'active' && $subscription->status !== 'suppressed') {
            $subscription->fill([
                'name' => $data['name'] ?? $subscription->name,
                'status' => 'pending',
                'locale' => app()->getLocale(),
                'user_id' => $request->user()?->id ?? $subscription->user_id,
                'confirmation_token' => Str::random(48),
            ])->save();

            $subscription->consents()->create([
                'purpose' => 'ministry_newsletter',
                'source' => 'website_form',
                'ip_address' => $request->ip(),
                'granted_at' => now(),
            ]);

            Notification::route('mail', $email)->notify(new NewsletterConfirmation($subscription));
        }

        return back()->with('newsletter_status', __('Please check your inbox to confirm your subscription.'));
    }

    public function confirm(string $token): View
    {
        $subscription = NewsletterSubscription::where('confirmation_token', $token)->first();
        abort_unless($subscription && $subscription->status === 'pending', 404);

        $subscription->update(['status' => 'active', 'subscribed_at' => now(), 'confirmation_token' => null, 'unsubscribed_at' => null]);
        SyncNewsletterSubscription::dispatch($subscription->id);

        return view('pages.message', [
            'title' => __('Subscription confirmed'),
            'message' => __('Thank you — you will now receive ministry news. You can unsubscribe at any time from any email.'),
        ]);
    }

    public function unsubscribe(Request $request, NewsletterSubscription $subscription): View
    {
        if ($request->isMethod('post') || $request->has('one-click')) {
            if ($subscription->status !== 'unsubscribed') {
                $subscription->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
                $subscription->consents()->whereNull('withdrawn_at')->update(['withdrawn_at' => now()]);
                Audit::log('unsubscribed', $subscription);
                SyncNewsletterSubscription::dispatch($subscription->id);
            }

            return view('pages.message', ['title' => __('Unsubscribed'), 'message' => __('You will no longer receive ministry newsletters.')]);
        }

        return view('pages.unsubscribe', ['subscription' => $subscription]);
    }
}
