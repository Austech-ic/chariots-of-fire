<?php

namespace App\Notifications;

use App\Models\NewsletterSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewsletterConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public NewsletterSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Please confirm your subscription'))
            ->line(__('Please confirm that you would like to receive ministry news from :site.', ['site' => config('app.name')]))
            ->action(__('Confirm subscription'), route('newsletter.confirm', $this->subscription->confirmation_token))
            ->line(__('If you did not request this, you can ignore this email and you will not be subscribed.'));
    }
}
