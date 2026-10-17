<?php

namespace App\Notifications;

use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistrationConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EventRegistration $registration) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->registration->event;

        return (new MailMessage)
            ->subject(__('Registration confirmed: :title', ['title' => $event->tr('title')]))
            ->line(__('You are registered for :title.', ['title' => $event->tr('title')]))
            ->line(__('When: :when', ['when' => $event->localStart()->translatedFormat('l j F Y, g:i a')]))
            ->line(__('Reference: :ref', ['ref' => $this->registration->registration_reference]))
            ->action(__('Event details'), route('events.show', $event));
    }
}
