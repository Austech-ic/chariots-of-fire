<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Models\CounsellingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Logistics only — never includes counselling narrative. Subjects stay generic.
 */
class AppointmentUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment, public string $event, public bool $forStaff = false) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->appointment->fresh(['service', 'counsellor']) ?? $this->appointment;
        $when = $a->localStart()->translatedFormat('l j F Y, g:i a').' ('.$a->timezone.')';
        $method = CounsellingService::METHODS[$a->method] ?? $a->method;

        if ($this->forStaff) {
            return (new MailMessage)
                ->subject(__('Appointment update').' · '.$a->reference)
                ->line(__('An appointment assigned to you has been updated (:event).', ['event' => $this->event]))
                ->line(__('Reference: :ref', ['ref' => $a->reference]))
                ->line(__('When: :when', ['when' => $when]))
                ->action(__('Open the secure dashboard'), url('/admin'));
        }

        $intro = match ($this->event) {
            'offered' => __('A counselling appointment time has been proposed for you. Please accept it or ask for another time.'),
            'received' => __('We have received your appointment request. It will be reviewed and you will receive a confirmation.'),
            'confirmed' => __('Your appointment is confirmed.'),
            'rescheduled' => __('Your appointment time has been changed.'),
            'cancelled' => __('Your appointment has been cancelled.'),
            default => __('Your appointment has been updated.'),
        };

        return (new MailMessage)
            ->subject(__('Your appointment with :site', ['site' => config('app.name')]))
            ->greeting(__('Hello :name,', ['name' => $a->contactName() ?? '']))
            ->line($intro)
            ->line(__('Reference: :ref', ['ref' => $a->reference]))
            ->line(__('When: :when', ['when' => $when]))
            ->line(__('Format: :method', ['method' => $method]))
            ->action(__('View or manage appointment'), route('appointments.manage', ['reference' => $a->reference, 'token' => $a->manage_token]));
    }
}
