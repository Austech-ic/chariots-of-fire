<?php

namespace Tests\Feature;

use App\Jobs\SendDonationReceipt;
use App\Models\Donation;
use App\Models\PaymentAttempt;
use App\Notifications\DonationReceipt;
use App\Services\Payments\DonationService;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaystackGateway;
use App\Services\Payments\TestGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DonationTest extends TestCase
{
    private function gateway(): TestGateway
    {
        return app(PaymentManager::class)->gateway('test');
    }

    private function startDonation(string $amount = '5000'): array
    {
        $response = $this->post('/give', ['amount' => $amount, 'currency' => 'NGN', 'campaign' => 'general', 'donor_email' => 'ada@example.org', 'donor_name' => 'Ada']);
        $donation = Donation::latest('id')->firstOrFail();

        return [$response, $donation, $donation->paymentAttempts()->firstOrFail()];
    }

    private function webhook(string $reference, string $id = 'evt_1', ?string $secret = 'test-secret')
    {
        $body = json_encode(['id' => $id, 'event' => 'charge.success', 'reference' => $reference]);
        $signature = $secret ? hash_hmac('sha256', $body, $secret) : 'bad';

        return $this->call('POST', '/webhooks/payments/test', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_TEST_SIGNATURE' => $signature], $body);
    }

    /** FCO-017 */
    public function test_initiating_creates_unique_pending_donation_and_checkout(): void
    {
        [$response, $donation, $attempt] = $this->startDonation('5000');

        $response->assertRedirect(route('give.test-checkout', $attempt->idempotency_key));
        $this->assertSame('pending', $donation->status);
        $this->assertSame(500000, $donation->amount_minor);
        $this->assertSame('NGN', $donation->currency);
        $this->assertStringStartsWith('DON-', $donation->reference);
    }

    public function test_amount_validation(): void
    {
        $this->post('/give', ['amount' => '1.5.0', 'currency' => 'NGN', 'donor_email' => 'a@example.org'])->assertSessionHasErrors('amount');
        $this->post('/give', ['amount' => '10', 'currency' => 'NGN', 'donor_email' => 'a@example.org'])->assertSessionHasErrors('amount'); // below minimum
        $this->post('/give', ['amount' => '5000', 'currency' => 'XYZ', 'donor_email' => 'a@example.org'])->assertSessionHasErrors('currency');
    }

    /** FCO-018 */
    public function test_failed_payment_is_not_recorded_as_paid(): void
    {
        [, $donation, $attempt] = $this->startDonation();
        $this->gateway()->settle($attempt->idempotency_key, 'failed');

        $this->get(route('give.callback', $donation->reference))->assertRedirect();
        $this->assertSame('failed', $donation->fresh()->status);
        $this->assertNull($donation->fresh()->paid_at);
    }

    public function test_forged_success_redirect_cannot_mark_payment_paid(): void
    {
        [, $donation] = $this->startDonation();
        // Browser returns to the callback claiming success, but the provider has not confirmed anything.
        $this->get(route('give.callback', $donation->reference).'?status=success&trxref=fake')->assertRedirect();
        $this->assertSame('pending', $donation->fresh()->status);
    }

    public function test_successful_verified_payment_is_paid_and_receipted_once(): void
    {
        Notification::fake();
        [, $donation, $attempt] = $this->startDonation();
        $this->gateway()->settle($attempt->idempotency_key, 'success');

        $this->get(route('give.callback', $donation->reference));
        $this->get(route('give.callback', $donation->reference));

        $fresh = $donation->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertNotNull($fresh->paid_at);
        $this->assertNotNull($fresh->receipt_sent_at);
        Notification::assertSentOnDemandTimes(DonationReceipt::class, 1);

        // The donor's own browser can view the status page; others cannot.
        $this->get(route('give.status', $donation->reference))->assertOk()->assertSee('Thank you for your gift');
        $this->flushSession();
        $this->get(route('give.status', $donation->reference))->assertNotFound();
    }

    /** FCO-019 */
    public function test_duplicate_webhooks_apply_one_state_transition(): void
    {
        Notification::fake();
        [, $donation, $attempt] = $this->startDonation();
        $this->gateway()->settle($attempt->idempotency_key, 'success');

        $this->webhook($attempt->idempotency_key, 'evt_1')->assertOk();
        $this->webhook($attempt->idempotency_key, 'evt_1')->assertOk();
        $this->webhook($attempt->idempotency_key, 'evt_2')->assertOk();

        $this->assertSame('paid', $donation->fresh()->status);
        $this->assertDatabaseCount('webhook_events', 2);
        $this->assertSame(1, \App\Models\AuditLog::where('action', 'Donation.paid')->count());
        Notification::assertSentOnDemandTimes(DonationReceipt::class, 1);
    }

    public function test_invalid_webhook_signature_cannot_change_status(): void
    {
        [, $donation, $attempt] = $this->startDonation();
        $this->gateway()->settle($attempt->idempotency_key, 'success');

        $this->webhook($attempt->idempotency_key, 'evt_x', null)->assertStatus(401);
        $this->assertSame('pending', $donation->fresh()->status);
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_amount_mismatch_is_flagged_not_paid(): void
    {
        [, $donation, $attempt] = $this->startDonation('5000');
        $this->gateway()->settle($attempt->idempotency_key, 'success', 100); // provider reports ₦1

        app(DonationService::class)->confirm($donation);
        $this->assertSame('mismatch', $donation->fresh()->reconciliation_status);
        $this->assertNotSame('paid', $donation->fresh()->status);
    }

    public function test_reconciliation_recovers_missed_webhooks_and_abandons_stale(): void
    {
        Notification::fake();
        [, $paidLater, $attempt] = $this->startDonation();
        [, $stale] = $this->startDonation();
        $this->gateway()->settle($attempt->idempotency_key, 'success');

        $this->travel(2)->days();
        $this->artisan('fco:reconcile-donations')->assertSuccessful();

        $this->assertSame('paid', $paidLater->fresh()->status);
        $this->assertSame('matched', $paidLater->fresh()->reconciliation_status);
        $this->assertSame('abandoned', $stale->fresh()->status);
    }

    public function test_refund_requires_permission_and_balance(): void
    {
        Notification::fake();
        [, $donation, $attempt] = $this->startDonation('5000');
        $this->gateway()->settle($attempt->idempotency_key, 'success');
        app(DonationService::class)->confirm($donation);

        $finance = $this->staff('finance');
        $editor = $this->staff('editor');
        $this->assertTrue($finance->can('refund', $donation->fresh()));
        $this->assertFalse($editor->can('refund', $donation->fresh()));

        app(DonationService::class)->refund($donation->fresh(), 200000, 'Duplicate gift', $finance);
        $this->assertSame('partially_refunded', $donation->fresh()->status);

        $this->expectException(\App\Services\Payments\PaymentException::class);
        app(DonationService::class)->refund($donation->fresh(), 400000, 'Too much', $finance);
    }

    public function test_member_donation_history_only_shows_verified_own_gifts(): void
    {
        Notification::fake();
        $member = $this->member();
        $other = $this->member();
        Donation::create(['reference' => 'DON-A', 'user_id' => $member->id, 'amount_minor' => 100000, 'currency' => 'NGN', 'status' => 'paid', 'provider' => 'test', 'paid_at' => now()]);
        Donation::create(['reference' => 'DON-B', 'user_id' => $member->id, 'amount_minor' => 100000, 'currency' => 'NGN', 'status' => 'pending', 'provider' => 'test']);
        Donation::create(['reference' => 'DON-C', 'user_id' => $other->id, 'amount_minor' => 100000, 'currency' => 'NGN', 'status' => 'paid', 'provider' => 'test', 'paid_at' => now()]);

        $this->actingAs($member)->get('/member/donations')->assertOk()->assertSee('DON-A')->assertDontSee('DON-B')->assertDontSee('DON-C');
        $this->actingAs($member)->get(route('give.status', 'DON-C'))->assertNotFound();
    }

    public function test_paystack_adapter_signature_and_verification(): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 500000, 'currency' => 'NGN', 'id' => 123]]),
        ]);
        $gateway = new PaystackGateway('sk_test_secret');

        $result = $gateway->verify('DON-1');
        $this->assertSame('succeeded', $result->status);
        $this->assertSame(500000, $result->amountMinor);

        $body = '{"event":"charge.success","data":{"id":9,"reference":"DON-1"}}';
        $good = Request::create('/', 'POST', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, 'sk_test_secret')], $body);
        $bad = Request::create('/', 'POST', [], [], [], ['HTTP_X_PAYSTACK_SIGNATURE' => 'nope'], $body);
        $this->assertTrue($gateway->verifyWebhookSignature($good));
        $this->assertFalse($gateway->verifyWebhookSignature($bad));
    }
}
