<?php

namespace App\Jobs;

use App\Models\Donation;
use App\Notifications\DonationReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/** Idempotent: a receipt is sent at most once per donation. */
class SendDonationReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $donationId) {}

    public function handle(): void
    {
        $claimed = DB::table('donations')
            ->where('id', $this->donationId)
            ->where('status', 'paid')
            ->whereNull('receipt_sent_at')
            ->update(['receipt_sent_at' => now()]);

        if (! $claimed) {
            return;
        }

        $donation = Donation::with('campaign')->find($this->donationId);
        if (! $donation?->donor_email) {
            return;
        }

        try {
            Notification::route('mail', $donation->donor_email)->notifyNow(new DonationReceipt($donation));
        } catch (\Throwable $e) {
            DB::table('donations')->where('id', $this->donationId)->update(['receipt_sent_at' => null]);

            throw $e;
        }
    }
}
