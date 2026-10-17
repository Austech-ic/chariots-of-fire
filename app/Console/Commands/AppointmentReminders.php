<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use App\Services\Messaging\MessagingService;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[AsCommand(name: 'fco:appointment-reminders', description: 'Send reminders for upcoming confirmed appointments')]
class AppointmentReminders extends Command
{
    protected $signature = 'fco:appointment-reminders';

    public function handle(MessagingService $messaging): int
    {
        $hours = config('fco.booking.reminder_hours_before');

        Appointment::query()->where('status', 'confirmed')->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [now(), now()->addHours($hours)])
            ->each(function (Appointment $appointment) use ($messaging) {
                // Claim first so overlapping runs never double-send.
                $claimed = Appointment::query()->whereKey($appointment->id)->whereNull('reminder_sent_at')->update(['reminder_sent_at' => now()]);
                if (! $claimed) {
                    return;
                }

                if ($email = $appointment->contactEmail()) {
                    Notification::route('mail', $email)->notify(new AppointmentReminder($appointment));
                }

                $phone = $appointment->contact_data['phone'] ?? $appointment->user?->phone;
                if ($phone) {
                    $params = ['reference' => $appointment->reference, 'when' => $appointment->localStart()->format('D j M, g:i a')];
                    foreach (['sms', 'whatsapp'] as $channel) {
                        $messaging->queue($channel, $phone, 'appointment_reminders', 'appointment_reminder', $params, $appointment, "reminder:{$channel}:{$appointment->id}:{$appointment->starts_at->timestamp}");
                    }
                }
            });

        return self::SUCCESS;
    }
}
