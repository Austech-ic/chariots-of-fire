<?php

namespace App\Services\Payments;

use App\Models\Donation;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Paystack hosted checkout (https://paystack.com/docs/api/). Amounts are in minor units. */
class PaystackGateway implements PaymentGateway
{
    public function __construct(
        private readonly ?string $secretKey,
        private readonly array $currencies = ['NGN'],
        private readonly string $baseUrl = 'https://api.paystack.co',
        private readonly ?string $fallbackEmail = null,
    ) {}

    public function key(): string
    {
        return 'paystack';
    }

    public function label(): string
    {
        return 'Paystack';
    }

    public function supportedCurrencies(): array
    {
        return $this->currencies;
    }

    private function client(): PendingRequest
    {
        if (! $this->secretKey) {
            throw new PaymentException('Paystack is not configured.');
        }

        return Http::baseUrl($this->baseUrl)->withToken($this->secretKey)->acceptJson()->timeout(15);
    }

    public function initiate(Donation $donation, string $reference, string $callbackUrl): CheckoutSession
    {
        $response = $this->client()->post('/transaction/initialize', [
            'email' => $donation->donor_email ?: $this->fallbackEmail,
            'amount' => $donation->amount_minor,
            'currency' => $donation->currency,
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => ['donation_reference' => $donation->reference, 'campaign' => $donation->campaign?->slug],
        ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new PaymentException('Paystack could not start checkout: '.($response->json('message') ?? $response->status()));
        }

        return new CheckoutSession($response->json('data.authorization_url'), $response->json('data.access_code'));
    }

    public function verify(string $reference): VerificationResult
    {
        $response = $this->client()->get('/transaction/verify/'.rawurlencode($reference));
        if ($response->status() === 404) {
            return VerificationResult::pending('Transaction not found yet');
        }
        if (! $response->successful()) {
            return VerificationResult::pending('Paystack verify error '.$response->status());
        }

        $data = $response->json('data') ?? [];
        $status = match ($data['status'] ?? null) {
            'success' => VerificationResult::SUCCEEDED,
            'failed', 'abandoned' => VerificationResult::FAILED,
            'reversed' => VerificationResult::REVERSED,
            default => VerificationResult::PENDING,
        };

        return new VerificationResult(
            $status,
            isset($data['amount']) ? (int) $data['amount'] : null,
            $data['currency'] ?? null,
            isset($data['id']) ? (string) $data['id'] : null,
            $data['status'] ?? null,
            $data['gateway_response'] ?? null,
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = (string) $request->header('x-paystack-signature');

        return $this->secretKey && $signature !== ''
            && hash_equals(hash_hmac('sha512', $request->getContent(), $this->secretKey), $signature);
    }

    public function parseWebhook(Request $request): ?WebhookPayload
    {
        $event = $request->input('event');
        $data = $request->input('data', []);
        if (! $event || ! is_array($data)) {
            return null;
        }

        return new WebhookPayload(
            $event.':'.($data['id'] ?? $data['reference'] ?? sha1($request->getContent())),
            $event,
            $data['reference'] ?? ($data['transaction']['reference'] ?? null),
        );
    }

    public function refund(Donation $donation, int $amountMinor, ?string $reason = null): RefundResult
    {
        $response = $this->client()->post('/refund', [
            'transaction' => $donation->provider_transaction_id ?: $donation->paymentAttempts()->latest('id')->value('idempotency_key'),
            'amount' => $amountMinor,
            'merchant_note' => $reason,
        ]);

        return new RefundResult(
            $response->successful() && $response->json('status'),
            isset($response->json('data')['id']) ? (string) $response->json('data.id') : null,
            $response->json('message'),
        );
    }
}
