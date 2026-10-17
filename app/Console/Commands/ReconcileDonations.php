<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Services\Payments\DonationService;
use App\Services\Payments\PaymentManager;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'fco:reconcile-donations', description: 'Verify pending donations with the provider and compare recent paid records')]
class ReconcileDonations extends Command
{
    protected $signature = 'fco:reconcile-donations {--days=7 : Re-check paid donations from the last N days}';

    public function handle(DonationService $donations, PaymentManager $payments): int
    {
        // 1. Pending donations: ask the provider (covers missed webhooks and closed browsers).
        Donation::query()->where('status', 'pending')->where('created_at', '<=', now()->subMinutes(10))
            ->each(function (Donation $donation) use ($donations, $payments) {
                if (! $payments->has($donation->provider)) {
                    return;
                }
                $fresh = $donations->confirm($donation, 'reconciliation');
                if ($fresh->status === 'pending' && $fresh->created_at->lt(now()->subDay())) {
                    $fresh->update(['status' => 'abandoned']);
                    $fresh->paymentAttempts()->where('status', 'initiated')->update(['status' => 'abandoned']);
                }
            });

        // 2. Paid donations: confirm the provider still reports the same amount.
        Donation::query()->where('status', 'paid')->where('reconciliation_status', '!=', 'matched')
            ->where('paid_at', '>=', now()->subDays((int) $this->option('days')))
            ->each(function (Donation $donation) use ($payments) {
                if (! $payments->has($donation->provider)) {
                    return;
                }
                $attempt = $donation->paymentAttempts()->where('status', 'succeeded')->latest('id')->first();
                if (! $attempt) {
                    return;
                }
                $result = $payments->gateway($donation->provider)->verify($attempt->idempotency_key);
                $matched = $result->status === 'succeeded' && $result->amountMinor === $donation->amount_minor && strtoupper((string) $result->currency) === $donation->currency;
                $donation->update(['reconciliation_status' => $matched ? 'matched' : 'mismatch', 'reconciled_at' => now()]);
            });

        $this->info('Reconciliation complete.');

        return self::SUCCESS;
    }
}
