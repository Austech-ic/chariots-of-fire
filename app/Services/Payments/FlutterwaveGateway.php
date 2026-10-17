<?php

namespace App\Services\Payments;

use App\Models\Donation;
use App\Support\Money;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Flutterwave Standard (v3). Flutterwave works in major units, so amounts are converted. */
class FlutterwaveGateway implements PaymentGateway
{
    public function __construct(
        private readonly ?string $secretKey,
        private readonly ?string $webhookHash,
        private readonly array $currencies = ['NGN'],
        private readonly string $baseUrl = 'https://api.flutterwave.com/v3',
        private readonly ?string $fallbackEmail = null,
    ) {}

    public function key(): string
    {
        return 'flutterwave';
    }

    public function label(): string
    {
        return 'Flutterwave';
    }

    public function supportedCurrencies(): array
    {
        return $this->currencies;
    }

    private function client(): PendingRequest
    {
        if (! $this->secretKey) {
            throw new PaymentException('Flutterwave is not configured.');
        }

        return Http::baseUrl($this->baseUrl)->withToken($this->secretKey)->acceptJson()->timeout(15);
    }

    private function toMajor(int $minor, string $currency): string
    {
        $exp = Money::exponent($currency);

        return $exp === 0 ? (string) $minor : number_format($minor / (10 ** $exp), $exp, '.', '');
    }

    public function initiate(Donation $donation, string $reference, string $callbackUrl): CheckoutSession
    {
        $response = $this->client()->post('/payments', [
            'tx_ref' => $reference,
            'amount' => $this->toMajor($donation->amount_minor, $donation->currency),
            'currency' => $donation->currency,
            'redirect_url' => $callbackUrl,
            'customer' => [
                'email' => $donation->donor_email ?: $this->fallbackEmail,
                'name' => $donation->is_anonymous ? 'Anonymous donor' : ($donation->donor_name ?: 'Donor'),
            ],
            'meta' => ['donation_reference' => $donation->reference],
            'customizations' => ['title' => config('app.name')],
        ]);

        if (! $response->successful() || $response->json('status') !== 'success') {
            throw new PaymentException('Flutterwave could not start checkout: '.($response->json('message') ?? $response->status()));
        }

        return new CheckoutSession($response->json('data.link'));
    }

    public function verify(string $reference): VerificationResult
    {
        $response = $this->client()->get('/transactions/verify_by_reference', ['tx_ref' => $reference]);
        if (! $response->successful()) {
            return VerificationResult::pending('Flutterwave verify error '.$response->status());
        }

        $data = $response->json('data') ?? [];
        $status = match ($data['status'] ?? null) {
            'successful' => VerificationResult::SUCCEEDED,
            'failed', 'cancelled' => VerificationResult::FAILED,
            default => VerificationResult::PENDING,
        };
        $currency = $data['currency'] ?? null;

        return new VerificationResult(
            $status,
            isset($data['amount'], $currency) ? Money::toMinor((string) $data['amount'], $currency) : null,
            $currency,
            isset($data['id']) ? (string) $data['id'] : null,
            $data['status'] ?? null,
            $data['processor_response'] ?? null,
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $hash = (string) $request->header('verif-hash');

        return $this->webhookHash && $hash !== '' && hash_equals($this->webhookHash, $hash);
    }

    public function parseWebhook(Request $request): ?WebhookPayload
    {
        $data = $request->input('data', []);
        $event = $request->input('event') ?? $request->input('event.type');
        if (! is_array($data) || ! $event) {
            return null;
        }

        return new WebhookPayload($event.':'.($data['id'] ?? sha1($request->getContent())), (string) $event, $data['tx_ref'] ?? null);
    }

    public function refund(Donation $donation, int $amountMinor, ?string $reason = null): RefundResult
    {
        if (! $donation->provider_transaction_id) {
            return new RefundResult(false, message: 'Missing Flutterwave transaction id');
        }

        $response = $this->client()->post('/transactions/'.$donation->provider_transaction_id.'/refund', [
            'amount' => $this->toMajor($amountMinor, $donation->currency),
            'comments' => $reason,
        ]);

        return new RefundResult($response->successful() && $response->json('status') === 'success', (string) $response->json('data.id'), $response->json('message'));
    }
}
