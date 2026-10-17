<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CounsellingService;
use App\Models\CounsellorProfile;
use App\Models\MemberResource;
use App\Models\NotificationConsent;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    public function test_registration_requires_email_verification(): void
    {
        Notification::fake();
        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.org', 'password' => 'secret12345', 'password_confirmation' => 'secret12345', 'privacy' => '1'])
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ada@example.org')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get('/member')->assertRedirect(route('verification.notice'));
        $this->assertFalse($user->is_staff);
    }

    public function test_login_and_logout(): void
    {
        $user = $this->member(['email' => 'ada@example.org', 'password' => 'secret12345']);
        $this->post('/login', ['email' => 'ada@example.org', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'ADA@example.org', 'password' => 'secret12345'])->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->member(['email' => 'ada@example.org']);
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'ada@example.org', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'ada@example.org', 'password' => 'wrong'])->assertStatus(429);
    }

    /** FCO-023 */
    public function test_member_only_resource_requires_authentication(): void
    {
        $resource = MemberResource::create(['slug' => 'study-guide', 'title' => 'Study guide', 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->get('/member/resources/study-guide')->assertRedirect(route('login'));
        $this->actingAs($this->member())->get('/member/resources/study-guide')->assertOk()->assertSee('Study guide');
    }

    public function test_granted_only_resources_are_not_reachable_by_url_guessing(): void
    {
        $member = $this->member();
        $resource = MemberResource::create(['slug' => 'leaders-pack', 'title' => 'Leaders pack', 'status' => 'published', 'access_policy' => 'granted_only']);

        $this->actingAs($member)->get('/member/resources/leaders-pack')->assertNotFound();
        $this->actingAs($member)->get('/member/resources')->assertDontSee('Leaders pack');

        $resource->grantedUsers()->attach($member, ['granted_at' => now()]);
        $this->actingAs($member)->get('/member/resources/leaders-pack')->assertOk();
    }

    public function test_member_cannot_open_another_members_appointment(): void
    {
        $owner = $this->member();
        $intruder = $this->member();
        $service = CounsellingService::firstOrFail();
        $counsellor = CounsellorProfile::create(['user_id' => $this->staff('counsellor')->id, 'display_name' => 'C']);
        $appointment = Appointment::create([
            'reference' => 'APT-1', 'counsellor_id' => $counsellor->id, 'service_id' => $service->id, 'user_id' => $owner->id,
            'contact_data' => ['name' => 'Owner'], 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'status' => 'confirmed', 'manage_token' => Str::random(48),
        ]);

        $this->actingAs($owner)->get('/appointments/APT-1')->assertOk();
        $this->actingAs($intruder)->get('/appointments/APT-1')->assertNotFound();
        $this->actingAs($intruder)->post('/appointments/APT-1/cancel')->assertNotFound();
        $this->actingAs($intruder)->get('/member/appointments')->assertDontSee('APT-1');
    }

    public function test_suspended_member_is_signed_out(): void
    {
        $member = $this->member();
        $this->actingAs($member)->get('/member')->assertOk();

        $member->update(['status' => User::STATUS_SUSPENDED]);
        $this->actingAs($member->fresh())->get('/member')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_sms_and_whatsapp_consent_and_immediate_opt_out(): void
    {
        $member = $this->member();
        $this->actingAs($member)->put('/member/preferences', ['phone' => '+2348000000001', 'channels' => ['sms.appointment_reminders', 'whatsapp.event_updates']])->assertSessionHas('status');
        $this->assertSame(2, NotificationConsent::where('status', 'granted')->count());

        $this->actingAs($member)->put('/member/preferences', ['phone' => '+2348000000001', 'channels' => ['sms.appointment_reminders']]);
        $this->assertSame('withdrawn', NotificationConsent::where('channel', 'whatsapp')->first()->status);

        $messaging = app(\App\Services\Messaging\MessagingService::class);
        $this->assertNotNull($messaging->queue('sms', '+2348000000001', 'appointment_reminders', 'appointment_reminder', ['reference' => 'X', 'when' => 'soon']));
        $this->assertNull($messaging->queue('whatsapp', '+2348000000001', 'event_updates', 'event_reminder', []));
    }

    public function test_account_deletion_request_workflow(): void
    {
        Notification::fake();
        $member = $this->member();
        $this->actingAs($member)->post('/member/account/requests', ['type' => 'deletion', 'details' => 'Please remove my data'])->assertSessionHas('status');
        $this->assertDatabaseHas('account_requests', ['user_id' => $member->id, 'type' => 'deletion', 'status' => 'open']);
    }
}
