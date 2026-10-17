<?php

namespace App\Jobs;

use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pushes consent status to the email marketing provider. This application is
 * the source of truth: an unsubscribe here always wins over provider state.
 */
class SyncNewsletterSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public int $subscriptionId) {}

    public function handle(): void
    {
        $sub = NewsletterSubscription::find($this->subscriptionId);
        $provider = config('fco.newsletter.provider');
        if (! $sub || $provider === 'none') {
            $sub?->update(['provider_sync_status' => 'not_synced']);

            return;
        }

        try {
            $active = $sub->status === 'active';
            match ($provider) {
                'mailchimp' => $this->mailchimp($sub, $active),
                'brevo' => $this->brevo($sub, $active),
            };
            $sub->update(['provider_sync_status' => 'synced', 'provider_sync_error' => null]);
        } catch (Throwable $e) {
            $sub->update(['provider_sync_status' => 'error', 'provider_sync_error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e;
        }
    }

    private function mailchimp(NewsletterSubscription $sub, bool $active): void
    {
        $cfg = config('fco.newsletter.mailchimp');
        $dc = substr(strrchr((string) $cfg['api_key'], '-') ?: '-us1', 1);
        Http::withBasicAuth('fco', $cfg['api_key'])->timeout(15)
            ->put("https://{$dc}.api.mailchimp.com/3.0/lists/{$cfg['list_id']}/members/".md5($sub->email_normalized), [
                'email_address' => $sub->email_normalized,
                'status_if_new' => $active ? 'subscribed' : 'unsubscribed',
                'status' => $active ? 'subscribed' : 'unsubscribed',
                'language' => $sub->locale,
            ])->throw();
    }

    private function brevo(NewsletterSubscription $sub, bool $active): void
    {
        $cfg = config('fco.newsletter.brevo');
        Http::withHeaders(['api-key' => $cfg['api_key']])->timeout(15)->post('https://api.brevo.com/v3/contacts', [
            'email' => $sub->email_normalized,
            'listIds' => $active ? [(int) $cfg['list_id']] : [],
            'unlinkListIds' => $active ? [] : [(int) $cfg['list_id']],
            'emailBlacklisted' => ! $active,
            'updateEnabled' => true,
        ])->throw();
    }
}
