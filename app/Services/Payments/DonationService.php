<?php

namespace App\Services\Payments;

use App\Jobs\SendDonationReceipt;
use App\Models\Donation;
use App\Models\DonationRefund;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Support\Audit;
use App\Support\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Donation lifecycle. A donation is only marked paid after the provider
 * confirms it server-to-server; browser redirects merely trigger that check.
 */
class DonationService
{
    public function __construct(private readonly PaymentManager $payments) {}

    /**
     * @param  array{amount_minor:int, currency:string, campaign_id?:?int, donor_name?:?string, donor_email?:?string, is_anonymous?:bool, note?:?string, frequency?:string, provider?:?string}  $data
     */
    public function start(array $data, ?User $user = null): array
    {
        $gateway = $this->payments->gateway($data['provider'] ?? null);

        if (! in_array($data['currency'], $gateway->supportedCurrencies(), true)) {
            throw new PaymentException('This currency is not supported by the payment provider.');
        }

        $donation = Donation::create([
            'reference' => Reference::make('DON'),
            'user_id' => $user?->id,
            'campaign_id' => $data['campaign_id'] ?? null,
            'amount_minor' => $data['amount_minor'],
            'currency' => $data['currency'],
            'frequency' => $data['frequency'] ?? 'one_time',
            'status' => 'pending',
            'provider' => $gateway->key(),
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'donor_name' => $data['donor_name'] ?? $user?->name,
            'donor_email' => $data['donor_email'] ?? $user?->email,
            'note' => $data['note'] ?? null,
        ]);

        // Each checkout attempt gets its own provider reference so retries never collide.
        $attemptReference = $donation->reference.'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $attempt = $donation->paymentAttempts()->create([
            'idempotency_key' => $attemptReference,
            'status' => 'initiated',
            'source' => 'checkout',
            'amount_minor' => $donation->amount_minor,
            'currency' => $donation->currency,
        ]);

        try {
            $session = $gateway->initiate($donation, $attemptReference, route('give.callback', ['donation' => $donation->reference]));
        } catch (Throwable $e) {
            $attempt->update(['status' => 'failed', 'failure_reason' => 'Checkout could not be started']);
            $donation->update(['status' => 'failed']);
            Log::warning('Donation checkout failed to start', ['donation' => $donation->reference, 'provider' => $gateway->key(), 'error' => $e->getMessage()]);

            throw $e instanceof PaymentException ? $e : new PaymentException('The payment provider is unavailable. Please try again shortly.', previous: $e);
        }

        $attempt->update(['checkout_url' => $session->checkoutUrl, 'provider_reference' => $session->providerReference]);

        return ['donation' => $donation, 'checkout_url' => $session->checkoutUrl];
    }

    /**
     * Verify every outstanding attempt of a donation with the provider and apply
     * the result. Safe to call any number of times from any source.
     */
    public function confirm(Donation $donation, string $source = 'verification'): Donation
    {
        $gateway = $this->payments->gateway($donation->provider);

        $attempts = $donation->paymentAttempts()->whereIn('status', ['initiated', 'succeeded'])->latest('id')->get();
        foreach ($attempts as $attempt) {
            try {
                $result = $gateway->verify($attempt->idempotency_key);
            } catch (Throwable $e) {
                Log::warning('Payment verification error', ['donation' => $donation->reference, 'error' => $e->getMessage()]);

                continue;
            }

            $this->apply($donation, $attempt, $result, $source);
            if ($donation->fresh()->status === 'paid') {
                break;
            }
        }

        return $donation->fresh();
    }

    /** Apply a verified provider result atomically and idempotently. */
    public function apply(Donation $donation, PaymentAttempt $attempt, VerificationResult $result, string $source): void
    {
        $becamePaid = DB::transaction(function () use ($donation, $attempt, $result, $source) {
            /** @var Donation $locked */
            $locked = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();
            $attempt = PaymentAttempt::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($result->status === VerificationResult::PENDING) {
                return false;
            }

            if ($result->status === VerificationResult::SUCCEEDED) {
                $matches = $result->amountMinor === $locked->amount_minor
                    && strtoupper((string) $result->currency) === $locked->currency;

                if (! $matches) {
                    $attempt->update([
                        'status' => 'failed',
                        'provider_status' => $result->providerStatus,
                        'failure_reason' => 'Amount or currency mismatch',
                        'verified_at' => now(),
                    ]);
                    $locked->update(['reconciliation_status' => 'mismatch']);
                    Audit::log('payment_mismatch', $locked, ['source' => $source]);

                    return false;
                }

                $attempt->update([
                    'status' => 'succeeded',
                    'source' => $source,
                    'provider_status' => $result->providerStatus,
                    'verified_at' => $attempt->verified_at ?? now(),
                ]);

                if ($locked->status === 'paid') {
                    return false; // duplicate callback or webhook — no second transition
                }
                if (! in_array($locked->status, ['pending', 'failed', 'abandoned'], true)) {
                    return false; // never resurrect refunded/reversed donations
                }

                $locked->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'provider_transaction_id' => $result->providerTransactionId,
                ]);
                Audit::log('paid', $locked, ['source' => $source, 'provider' => $locked->provider]);

                return true;
            }

            if ($result->status === VerificationResult::REVERSED) {
                $attempt->update(['provider_status' => $result->providerStatus, 'verified_at' => now()]);
                if ($locked->status === 'paid') {
                    $locked->update(['status' => 'reversed']);
                    Audit::log('reversed', $locked, ['source' => $source]);
                }

                return false;
            }

            // FAILED: record it; only a still-pending donation changes state.
            $attempt->update([
                'status' => 'failed',
                'provider_status' => $result->providerStatus,
                'failure_reason' => $result->message,
                'verified_at' => now(),
            ]);
            if ($locked->status === 'pending' && ! $locked->paymentAttempts()->where('status', 'initiated')->exists()) {
                $locked->update(['status' => 'failed']);
            }

            return false;
        });

        if ($becamePaid) {
            SendDonationReceipt::dispatch($donation->getKey())->afterCommit();
        }
    }

    /**
     * Process a provider webhook. The payload is only used to find the
     * donation; its status is always re-verified with the provider.
     *
     * @return int HTTP status to return to the provider
     */
    public function handleWebhook(string $provider, Request $request): int
    {
        if (! $this->payments->has($provider)) {
            return 404;
        }

        $gateway = $this->payments->gateway($provider);
        if (! $gateway->verifyWebhookSignature($request)) {
            Log::notice('Rejected webhook with invalid signature', ['provider' => $provider, 'ip' => $request->ip()]);

            return 401;
        }

        $payload = $gateway->parseWebhook($request);
        if (! $payload) {
            return 400;
        }

        $event = WebhookEvent::firstOrCreate(
            ['provider' => $provider, 'external_event_id' => $payload->eventId],
            ['event_type' => $payload->eventType, 'status' => 'received', 'received_at' => now()],
        );

        if ($event->status === 'processed') {
            return 200; // duplicate delivery
        }

        try {
            $attempt = $payload->reference
                ? PaymentAttempt::query()->where('idempotency_key', $payload->reference)->first()
                : null;

            if ($attempt) {
                $this->confirm($attempt->donation, 'webhook');
                $event->update(['status' => 'processed', 'processed_at' => now()]);
            } else {
                $event->update(['status' => 'ignored', 'processed_at' => now(), 'error' => 'No matching donation reference']);
            }
        } catch (Throwable $e) {
            report($e);
            $event->update(['status' => 'failed', 'error' => 'Processing error']);

            return 500; // provider will retry
        }

        return 200;
    }

    public function refund(Donation $donation, int $amountMinor, ?string $reason, User $actor): DonationRefund
    {
        if (! $donation->isPaid() && $donation->status !== 'partially_refunded') {
            throw new PaymentException('Only paid donations can be refunded.');
        }

        $remaining = $donation->amount_minor - $donation->refundedMinor();
        if ($amountMinor <= 0 || $amountMinor > $remaining) {
            throw new PaymentException('Refund amount exceeds the refundable balance.');
        }

        $result = $this->payments->gateway($donation->provider)->refund($donation, $amountMinor, $reason);

        return DB::transaction(function () use ($donation, $amountMinor, $reason, $actor, $result, $remaining) {
            $refund = $donation->refunds()->create([
                'amount_minor' => $amountMinor,
                'reason' => $reason,
                'provider_reference' => $result->providerReference,
                'status' => $result->accepted ? 'processed' : 'failed',
                'requested_by' => $actor->id,
            ]);

            if ($result->accepted) {
                $donation->update(['status' => $amountMinor === $remaining ? 'refunded' : 'partially_refunded']);
            }

            Audit::log('refund_requested', $donation, ['accepted' => $result->accepted], $actor->id);

            return $refund;
        });
    }
}
