<?php

namespace App\Services\Payments;

use App\Models\Donation;
use Illuminate\Http\Request;

/**
 * Provider-agnostic payment contract. Implementations must never collect
 * card data: checkout always happens on the provider's hosted page.
 */
interface PaymentGateway
{
    public function key(): string;

    public function label(): string;

    /** @return list<string> ISO currency codes the provider account can accept. */
    public function supportedCurrencies(): array;

    public function initiate(Donation $donation, string $reference, string $callbackUrl): CheckoutSession;

    /** Authoritative server-to-server lookup of a transaction by our reference. */
    public function verify(string $reference): VerificationResult;

    public function verifyWebhookSignature(Request $request): bool;

    public function parseWebhook(Request $request): ?WebhookPayload;

    public function refund(Donation $donation, int $amountMinor, ?string $reason = null): RefundResult;
}
