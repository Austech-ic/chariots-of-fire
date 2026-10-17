<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AvailabilityException;
use App\Models\AvailabilityRule;
use App\Models\CounsellingRequest;
use App\Models\CounsellingService;
use App\Models\CounsellorProfile;
use App\Notifications\AppointmentUpdated;
use App\Notifications\StaffAlert;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingException;
use App\Services\Booking\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CounsellingTest extends TestCase
{
    private CounsellingService $service;

    private CounsellorProfile $counsellor;

    protected function setUp(): void
    {
        parent::setUp();
        // Wednesday 10:00 Lagos time; slots exist on Thursdays.
        $this->travelTo(CarbonImmutable::parse('2026-11-04 10:00', 'Africa/Lagos'));

        $this->service = CounsellingService::where('slug', 'spiritual-guidance')->firstOrFail();
        $this->service->update(['approval_required' => false, 'min_notice_hours' => 2, 'duration_minutes' => 60, 'buffer_minutes' => 15]);

        $user = $this->staff('counsellor');
        $this->counsellor = CounsellorProfile::create(['user_id' => $user->id, 'display_name' => 'Pastor Test']);
        $this->counsellor->services()->attach($this->service);
        AvailabilityRule::create(['counsellor_id' => $this->counsellor->id, 'weekday' => 4, 'start_time' => '09:00', 'end_time' => '12:00', 'timezone' => 'Africa/Lagos']);
    }

    private function thursday(string $time): CarbonImmutable
    {
        return CarbonImmutable::parse("2026-11-05 {$time}", 'Africa/Lagos')->utc();
    }

    private function bookingPayload(string $time, array $overrides = []): array
    {
        return $overrides + [
            'service' => $this->service->slug,
            'slot' => $this->counsellor->id.'|'.$this->thursday($time)->format('Y-m-d\TH:i:s\Z'),
            'method' => 'in_person',
            'name' => 'Ada Visitor',
            'email' => 'ada@example.org',
            'consent' => '1',
        ];
    }

    public function test_availability_respects_rules_buffers_exceptions_and_notice(): void
    {
        $slots = app(AvailabilityService::class)->slots($this->service, CarbonImmutable::now(), CarbonImmutable::now()->addDays(2));
        $times = $slots->map(fn ($s) => $s->start->setTimezone('Africa/Lagos')->format('H:i'))->all();
        $this->assertSame(['09:00', '10:15'], $times); // 60 min + 15 min buffer; 11:30 would end after 12:00

        AvailabilityException::create(['counsellor_id' => $this->counsellor->id, 'starts_at' => $this->thursday('09:00'), 'ends_at' => $this->thursday('10:00')]);
        $times = app(AvailabilityService::class)->slots($this->service, CarbonImmutable::now(), CarbonImmutable::now()->addDays(2))
            ->map(fn ($s) => $s->start->setTimezone('Africa/Lagos')->format('H:i'))->all();
        $this->assertSame(['10:15'], $times);
    }

    /** FCO-013 */
    public function test_counselling_request_is_encrypted_and_staff_get_generic_alert(): void
    {
        Notification::fake();
        $coordinator = $this->staff('counselling-coordinator');

        $this->post('/counselling/request', [
            'name' => 'Ada', 'email' => 'ada@example.org', 'preferred_contact' => 'email',
            'message' => 'A very private matter about my family.', 'consent' => '1',
        ])->assertRedirect();

        $request = CounsellingRequest::firstOrFail();
        $this->assertSame('A very private matter about my family.', $request->encrypted_message);
        $raw = DB::table('counselling_requests')->first();
        $this->assertStringNotContainsString('private matter', $raw->encrypted_message);
        $this->assertStringNotContainsString('ada@example.org', $raw->contact_data);

        Notification::assertSentTo($coordinator, StaffAlert::class, fn ($n) => ! str_contains($n->message, 'private matter'));
    }

    /** FCO-014 */
    public function test_self_service_booking_stores_utc_and_confirms(): void
    {
        Notification::fake();
        $this->post('/counselling/book', $this->bookingPayload('09:00'))->assertRedirect();

        $appointment = Appointment::firstOrFail();
        $this->assertSame('confirmed', $appointment->status);
        $this->assertSame('2026-11-05 08:00:00', $appointment->starts_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('09:00', $appointment->localStart()->format('H:i'));
        Notification::assertSentOnDemand(AppointmentUpdated::class);
    }

    public function test_service_requiring_approval_creates_pending_booking(): void
    {
        Notification::fake();
        $this->service->update(['approval_required' => true]);
        $this->post('/counselling/book', $this->bookingPayload('09:00'));
        $this->assertSame('pending', Appointment::firstOrFail()->status);
    }

    /** FCO-015 */
    public function test_the_same_slot_cannot_be_booked_twice(): void
    {
        Notification::fake();
        $this->post('/counselling/book', $this->bookingPayload('09:00'))->assertRedirect();
        $this->post('/counselling/book', $this->bookingPayload('09:00', ['email' => 'bola@example.org']))->assertSessionHasErrors('slot');

        // Overlapping start inside the buffer is also refused at service level.
        $this->expectException(BookingException::class);
        app(BookingService::class)->book($this->service, $this->counsellor->id, $this->thursday('09:30'), ['name' => 'C', 'email' => 'c@example.org'], 'in_person');
    }

    public function test_slot_in_the_past_or_outside_hours_is_rejected(): void
    {
        $this->post('/counselling/book', $this->bookingPayload('13:00'))->assertSessionHasErrors('slot');
        $this->assertSame(0, Appointment::count());
    }

    /** FCO-016 */
    public function test_reschedule_and_cancel_follow_rules(): void
    {
        Notification::fake();
        $appointment = app(BookingService::class)->book($this->service, $this->counsellor->id, $this->thursday('09:00'), ['name' => 'A', 'email' => 'a@example.org'], 'in_person');
        $coordinator = $this->staff('counselling-coordinator');

        app(BookingService::class)->reschedule($appointment, $this->thursday('10:15'), $coordinator);
        $this->assertSame('10:15', $appointment->fresh()->localStart()->format('H:i'));

        // Visitor cancels using the emailed token.
        $this->post("/appointments/{$appointment->reference}/cancel", ['token' => 'wrong'])->assertNotFound();
        $this->post("/appointments/{$appointment->reference}/cancel", ['token' => $appointment->manage_token])->assertSessionHas('status');
        $this->assertSame('cancelled', $appointment->fresh()->status);

        // The freed slot is bookable again.
        $this->assertTrue(app(AvailabilityService::class)->isAvailable($this->service, $this->counsellor->id, $this->thursday('10:15')));
    }

    public function test_staff_approved_path_offer_and_accept(): void
    {
        Notification::fake();
        $coordinator = $this->staff('counselling-coordinator');
        $request = CounsellingRequest::create(['reference' => 'CR-TEST', 'contact_data' => ['name' => 'Ada', 'email' => 'ada@example.org'], 'encrypted_message' => 'x', 'status' => 'new', 'service_id' => $this->service->id]);

        $offer = app(BookingService::class)->offer($request, $this->counsellor->id, $this->thursday('09:00'), 'phone', $coordinator);
        $this->assertSame('offered', $offer->status);
        $this->assertSame('offered', $request->fresh()->status);

        $this->post("/appointments/{$offer->reference}/accept", ['token' => $offer->manage_token])->assertSessionHas('status');
        $this->assertSame('confirmed', $offer->fresh()->status);
        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_offer_outside_availability_needs_logged_override(): void
    {
        $coordinator = $this->staff('counselling-coordinator');
        $request = CounsellingRequest::create(['reference' => 'CR-OVR', 'contact_data' => ['name' => 'A'], 'encrypted_message' => 'x', 'status' => 'new']);

        try {
            app(BookingService::class)->offer($request, $this->counsellor->id, $this->thursday('17:00'), 'phone', $coordinator);
            $this->fail('Expected availability check');
        } catch (BookingException) {
        }

        Notification::fake();
        app(BookingService::class)->offer($request, $this->counsellor->id, $this->thursday('17:00'), 'phone', $coordinator, ignoreAvailability: true);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Appointment.availability_override', 'actor_user_id' => $coordinator->id]);
    }

    public function test_reminders_are_sent_once(): void
    {
        Notification::fake();
        app(BookingService::class)->book($this->service, $this->counsellor->id, $this->thursday('09:00'), ['name' => 'A', 'email' => 'a@example.org'], 'in_person');
        $this->artisan('fco:appointment-reminders');
        $this->artisan('fco:appointment-reminders');
        Notification::assertSentOnDemandTimes(\App\Notifications\AppointmentReminder::class, 1);
    }
}
