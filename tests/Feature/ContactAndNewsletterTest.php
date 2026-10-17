<?php

namespace Tests\Feature;

use App\Models\ContactEnquiry;
use App\Models\NewsletterSubscription;
use App\Notifications\NewsletterConfirmation;
use App\Notifications\StaffAlert;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactAndNewsletterTest extends TestCase
{
    /** FCO-011 */
    public function test_valid_contact_form_is_stored_and_acknowledged(): void
    {
        Notification::fake();
        $staff = $this->staff('communications');

        $this->post('/contact', ['name' => 'Ada', 'email' => 'ada@example.org', 'subject' => 'Visit', 'message' => 'We would like to visit on Sunday.'])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('contact_enquiries', ['email' => 'ada@example.org', 'status' => 'new']);
        Notification::assertSentTo($staff, StaffAlert::class);
    }

    /** FCO-012 */
    public function test_invalid_contact_form_is_rejected(): void
    {
        $this->post('/contact', ['name' => '', 'email' => 'not-an-email', 'subject' => '', 'message' => 'short'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
        $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.org', 'subject' => 'x', 'message' => 'Buy things now please', 'website' => 'spam'])
            ->assertSessionHasErrors('website');
        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_contact_form_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', ['name' => 'A', 'email' => 'a@example.org', 'subject' => 's', 'message' => 'A long enough message']);
        }
        $this->post('/contact', ['name' => 'A', 'email' => 'a@example.org', 'subject' => 's', 'message' => 'A long enough message'])->assertStatus(429);
    }

    /** FCO-020 */
    public function test_newsletter_double_opt_in_records_consent(): void
    {
        Notification::fake();
        $this->post('/newsletter/subscribe', ['email' => ' Ada@Example.org ', 'consent' => '1'])->assertSessionHas('newsletter_status');

        $sub = NewsletterSubscription::firstOrFail();
        $this->assertSame('ada@example.org', $sub->email_normalized);
        $this->assertSame('pending', $sub->status);
        $this->assertSame(1, $sub->consents()->count());
        Notification::assertSentOnDemand(NewsletterConfirmation::class);

        $this->get('/newsletter/confirm/'.$sub->confirmation_token)->assertOk();
        $this->assertSame('active', $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->subscribed_at);
    }

    public function test_newsletter_requires_consent(): void
    {
        $this->post('/newsletter/subscribe', ['email' => 'a@example.org'])->assertSessionHasErrors('consent');
    }

    /** FCO-021 */
    public function test_unsubscribe_suppresses_marketing(): void
    {
        $sub = NewsletterSubscription::create(['email_normalized' => 'ada@example.org', 'status' => 'active', 'subscribed_at' => now()]);
        $sub->consents()->create(['purpose' => 'ministry_newsletter', 'source' => 'test', 'granted_at' => now()]);

        $this->get('/newsletter/unsubscribe/'.$sub->id)->assertForbidden(); // unsigned
        $this->post($sub->unsubscribeUrl())->assertOk();

        $this->assertSame('unsubscribed', $sub->fresh()->status);
        $this->assertFalse($sub->fresh()->canReceiveMarketing());
        $this->assertNotNull($sub->consents()->first()->withdrawn_at);
    }
}
