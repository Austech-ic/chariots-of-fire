<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->appointment;

        return (new MailMessage)
            ->subject(__('Appointment reminder'))
            ->line(__('This is a reminder of your upcoming appointment.'))
            ->line(__('When: :when', ['when' => $a->localStart()->translatedFormat('l j F Y, g:i a')]))
            ->line(__('Reference: :ref', ['ref' => $a->reference]))
            ->action(__('View or manage appointment'), route('appointments.manage', ['reference' => $a->reference, 'token' => $a->manage_token]));
    }
}
