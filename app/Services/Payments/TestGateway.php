<?php

namespace App\Services\Payments;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Simulated hosted checkout for local development, staging demos and tests.
 * Disabled automatically in production (see PaymentManager).
 */
class TestGateway implements PaymentGateway
{
    public function __construct(private readonly string $webhookSecret = 'test-secret') {}

    public function key(): string
    {
        return 'test';
    }

    public function label(): string
    {
        return 'Test gateway (no real money)';
    }

    public function supportedCurrencies(): array
    {
        return ['NGN', 'USD', 'GBP', 'EUR'];
    }

    public function initiate(Donation $donation, string $reference, string $callbackUrl): CheckoutSession
    {
        Cache::put($this->cacheKey($reference), ['status' => 'pending', 'amount' => $donation->amount_minor, 'currency' => $donation->currency, 'callback' => $callbackUrl], now()->addDays(30));

        return new CheckoutSession(route('give.test-checkout', ['reference' => $reference]), $reference);
    }

    /** Called by the simulated checkout page (or tests) to settle a transaction. */
    public function settle(string $reference, string $status, ?int $amountMinor = null): void
    {
        $record = Cache::get($this->cacheKey($reference), []);
        $record['status'] = $status;
        if ($amountMinor !== null) {
            $record['amount'] = $amountMinor;
        }
        Cache::put($this->cacheKey($reference), $record, now()->addDays(30));
    }

    public function callbackFor(string $reference): ?string
    {
        return Cache::get($this->cacheKey($reference))['callback'] ?? null;
    }

    public function verify(string $reference): VerificationResult
    {
        $record = Cache::get($this->cacheKey($reference));
        if (! $record) {
            return VerificationResult::pending('Unknown test transaction');
        }

        $status = match ($record['status']) {
            'success' => VerificationResult::SUCCEEDED,
            'failed' => VerificationResult::FAILED,
            'reversed' => VerificationResult::REVERSED,
            default => VerificationResult::PENDING,
        };

        return new VerificationResult($status, $record['amount'], $record['currency'], 'test_'.$reference, $record['status']);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = (string) $request->header('x-test-signature');

        return $signature !== '' && hash_equals(hash_hmac('sha256', $request->getContent(), $this->webhookSecret), $signature);
    }

    public function parseWebhook(Request $request): ?WebhookPayload
    {
        $id = $request->input('id');
        $reference = $request->input('reference');

        return $id && $reference ? new WebhookPayload((string) $id, (string) $request->input('event', 'charge.success'), (string) $reference) : null;
    }

    public function refund(Donation $donation, int $amountMinor, ?string $reason = null): RefundResult
    {
        return new RefundResult(true, 'test_refund_'.$donation->reference);
    }

    private function cacheKey(string $reference): string
    {
        return 'test-gateway.'.$reference;
    }
}
