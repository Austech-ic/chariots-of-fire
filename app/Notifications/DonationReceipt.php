<?php

namespace App\Notifications;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonationReceipt extends Notification
{
    use Queueable;

    public function __construct(public Donation $donation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->donation;

        return (new MailMessage)
            ->subject(__('Thank you for your gift').' · '.$d->reference)
            ->greeting(__('Thank you:name', ['name' => $d->donor_name && ! $d->is_anonymous ? ', '.$d->donor_name : '']))
            ->line(__('We have received your donation of :amount.', ['amount' => $d->formattedAmount()]))
            ->line(__('Purpose: :purpose', ['purpose' => $d->campaign?->name ?? __('General giving')]))
            ->line(__('Reference: :ref', ['ref' => $d->reference]))
            ->line(__('Date: :date', ['date' => $d->paid_at?->setTimezone(config('app.display_timezone'))->translatedFormat('j F Y, g:i a')]))
            ->line(__('Please keep this email as your record of giving.'));
    }
}
