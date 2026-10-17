<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic staff notification that points to the secure dashboard instead of
 * carrying sensitive content in the email itself.
 */
class StaffAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $subject, public string $message, public string $url) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->message)
            ->action(__('Open the secure dashboard'), $this->url);
    }
}
