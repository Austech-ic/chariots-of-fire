<?php

namespace App\Services\Booking;

use App\Models\Appointment;
use App\Models\CounsellingRequest;
use App\Models\CounsellingService;
use App\Models\CounsellorProfile;
use App\Models\User;
use App\Notifications\AppointmentUpdated;
use App\Support\Audit;
use App\Support\Reference;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Path B: self-service booking. Availability is re-checked while the
     * counsellor row is locked, so concurrent requests serialise and at most
     * one can take a given slot.
     *
     * @param  array{name:string, email:string, phone?:?string}  $contact
     */
    public function book(CounsellingService $service, int $counsellorId, CarbonImmutable $start, array $contact, string $method, ?User $user = null): Appointment
    {
        if (! $service->allowsSelfService()) {
            throw new BookingException('This service does not offer online booking.');
        }
        if ($service->requires_account && ! $user) {
            throw new BookingException('Please sign in to book this service.');
        }
        if (! in_array($method, $service->methods(), true)) {
            throw new BookingException('That appointment method is not available for this service.');
        }

        $appointment = DB::transaction(function () use ($service, $counsellorId, $start, $contact, $method, $user) {
            $this->lockCounsellor($counsellorId);

            if (! $this->availability->isAvailable($service, $counsellorId, $start)) {
                throw new BookingException('Sorry, that time is no longer available. Please choose another slot.');
            }

            return Appointment::create([
                'reference' => Reference::make('APT'),
                'counsellor_id' => $counsellorId,
                'service_id' => $service->id,
                'user_id' => $user?->id,
                'contact_data' => array_filter($contact),
                'starts_at' => $start->utc(),
                'ends_at' => $start->utc()->addMinutes($service->duration_minutes),
                'timezone' => config('app.display_timezone'),
                'method' => $method,
                'status' => $service->approval_required ? 'pending' : 'confirmed',
                'source' => 'self_service',
                'manage_token' => Str::random(48),
            ]);
        });

        $this->notify($appointment, $appointment->status === 'confirmed' ? 'confirmed' : 'received');

        return $appointment;
    }

    /** Path A: a coordinator proposes a time for a counselling request. */
    public function offer(CounsellingRequest $request, int $counsellorId, CarbonImmutable $start, string $method, User $actor, bool $ignoreAvailability = false): Appointment
    {
        $service = $request->service ?? CounsellingService::query()->active()->firstOrFail();

        $appointment = DB::transaction(function () use ($request, $service, $counsellorId, $start, $method, $actor, $ignoreAvailability) {
            $this->lockCounsellor($counsellorId);
            $end = $start->utc()->addMinutes($service->duration_minutes);

            $clash = Appointment::query()->blocking()->where('counsellor_id', $counsellorId)->overlapping($start->utc(), $end)->exists();
            if ($clash) {
                throw new BookingException('The counsellor already has an appointment at that time.');
            }
            if (! $ignoreAvailability && ! $this->availability->isAvailable($service, $counsellorId, $start)) {
                throw new BookingException('That time is outside the counsellor\'s availability. Use the override option to proceed.');
            }

            // Supersede any earlier offer that was never accepted.
            $request->appointments()->where('status', 'offered')->update(['status' => 'cancelled', 'cancellation_reason' => 'Superseded by a new offer']);

            $appointment = Appointment::create([
                'reference' => Reference::make('APT'),
                'counselling_request_id' => $request->id,
                'counsellor_id' => $counsellorId,
                'service_id' => $service->id,
                'user_id' => $request->requester_user_id,
                'contact_data' => $request->contact_data,
                'starts_at' => $start->utc(),
                'ends_at' => $end,
                'timezone' => config('app.display_timezone'),
                'method' => $method,
                'status' => 'offered',
                'source' => 'staff',
                'manage_token' => Str::random(48),
                'updated_by' => $actor->id,
            ]);

            $request->update(['status' => 'offered', 'assigned_to' => $counsellorId]);

            if ($ignoreAvailability) {
                Audit::log('availability_override', $appointment, [], $actor->id);
            }

            return $appointment;
        });

        $this->notify($appointment, 'offered');

        return $appointment;
    }

    public function accept(Appointment $appointment): Appointment
    {
        DB::transaction(function () use ($appointment) {
            $this->lockCounsellor($appointment->counsellor_id);
            $appointment->refresh();
            if ($appointment->status !== 'offered' || $appointment->starts_at->isPast()) {
                throw new BookingException('This offer can no longer be accepted.');
            }
            $appointment->update(['status' => 'confirmed']);
            $appointment->counsellingRequest?->update(['status' => 'scheduled']);
        });

        $this->notify($appointment, 'confirmed');

        return $appointment;
    }

    public function decline(Appointment $appointment, ?string $note = null): void
    {
        if ($appointment->status !== 'offered') {
            throw new BookingException('This offer is no longer open.');
        }
        $appointment->update(['status' => 'cancelled', 'cancellation_reason' => 'Declined by visitor'.($note ? ': '.$note : '')]);
        $appointment->counsellingRequest?->update(['status' => 'assigned']);
        Audit::log('offer_declined', $appointment);
    }

    public function reschedule(Appointment $appointment, CarbonImmutable $start, ?User $actor = null, bool $override = false): Appointment
    {
        if (! $appointment->isChangeable()) {
            throw new BookingException('This appointment can no longer be changed.');
        }

        DB::transaction(function () use ($appointment, $start, $actor, $override) {
            $this->lockCounsellor($appointment->counsellor_id);
            $service = $appointment->service;
            $end = $start->utc()->addMinutes($service->duration_minutes);

            $clash = Appointment::query()->blocking()->where('counsellor_id', $appointment->counsellor_id)
                ->whereKeyNot($appointment->id)->overlapping($start->utc(), $end)->exists();
            if ($clash) {
                throw new BookingException('That time clashes with another appointment.');
            }

            // Release the current slot before checking availability for the new one.
            $previousStatus = $appointment->status;
            $appointment->update(['status' => 'cancelled']);
            $available = $override || $this->availability->isAvailable($service, $appointment->counsellor_id, $start);
            if (! $available) {
                $appointment->update(['status' => $previousStatus]);
                throw new BookingException('That time is not available.');
            }

            $appointment->update([
                'starts_at' => $start->utc(),
                'ends_at' => $end,
                'status' => $actor ? ($previousStatus === 'offered' ? 'offered' : 'confirmed') : ($service->approval_required ? 'pending' : 'confirmed'),
                'reminder_sent_at' => null,
                'updated_by' => $actor?->id,
            ]);

            if ($override && $actor) {
                Audit::log('availability_override', $appointment, [], $actor->id);
            }
        });

        $this->notify($appointment->refresh(), 'rescheduled');

        return $appointment;
    }

    public function cancel(Appointment $appointment, ?string $reason = null, ?User $actor = null): void
    {
        if (! in_array($appointment->status, Appointment::BLOCKING, true)) {
            throw new BookingException('This appointment is not active.');
        }
        $appointment->update(['status' => 'cancelled', 'cancellation_reason' => $reason, 'updated_by' => $actor?->id]);
        $this->notify($appointment, 'cancelled');
    }

    private function lockCounsellor(int $counsellorId): void
    {
        $profile = CounsellorProfile::query()->whereKey($counsellorId)->lockForUpdate()->first();
        if (! $profile || ! $profile->isBookable()) {
            throw new BookingException('This counsellor is not currently taking bookings.');
        }
    }

    /** Messages contain only logistics, never the counselling narrative. */
    private function notify(Appointment $appointment, string $event): void
    {
        $email = $appointment->contactEmail();
        if ($email) {
            Notification::route('mail', $email)->notify(new AppointmentUpdated($appointment, $event));
        }

        $counsellor = $appointment->counsellor?->user;
        if ($counsellor && in_array($event, ['received', 'confirmed', 'cancelled', 'rescheduled'], true)) {
            $counsellor->notify(new AppointmentUpdated($appointment, $event, forStaff: true));
        }
    }
}
