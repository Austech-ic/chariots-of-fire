<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\ContactEnquiry;
use App\Models\CounsellingRequest;
use App\Models\Donation;
use App\Models\Event;
use App\Models\MediaAsset;
use App\Models\NewsletterSubscription;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Each tile appears only for staff permitted to see the underlying records. */
class MinistryOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        if ($user->hasPermission('counselling.coordinate')) {
            $stats[] = Stat::make('New counselling requests', CounsellingRequest::where('status', 'new')->count())->color('danger')->icon('heroicon-o-chat-bubble-left-right');
        }
        if ($user->hasPermission('counselling.coordinate') || $user->hasPermission('counselling.counsel')) {
            $q = Appointment::query()->whereIn('status', ['pending', 'confirmed'])->whereBetween('starts_at', [now(), now()->addDays(7)]);
            if (! $user->hasPermission('counselling.coordinate')) {
                $q->where('counsellor_id', $user->counsellorProfile?->id ?? 0);
            }
            $stats[] = Stat::make('Appointments next 7 days', $q->count())->icon('heroicon-o-calendar');
        }
        if ($user->hasPermission('donations.view')) {
            $month = Donation::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth());
            $byCurrency = $month->selectRaw('currency, SUM(amount_minor) as total')->groupBy('currency')->pluck('total', 'currency');
            $stats[] = Stat::make('Giving this month', $byCurrency->map(fn ($t, $c) => Money::format((int) $t, $c))->implode(' · ') ?: '—')
                ->description(Donation::where('reconciliation_status', 'mismatch')->count().' reconciliation issue(s)')->icon('heroicon-o-banknotes');
        }
        if ($user->hasPermission('events.manage')) {
            $stats[] = Stat::make('Upcoming programmes', Event::published()->where('starts_at', '>=', now())->count())->icon('heroicon-o-calendar-days');
        }
        if ($user->hasPermission('enquiries.manage')) {
            $stats[] = Stat::make('New enquiries', ContactEnquiry::where('status', 'new')->count())->icon('heroicon-o-inbox-arrow-down');
        }
        if ($user->hasPermission('newsletter.manage')) {
            $stats[] = Stat::make('Newsletter subscribers', NewsletterSubscription::where('status', 'active')->count())->icon('heroicon-o-envelope');
        }
        if ($user->hasPermission('media.manage')) {
            $failed = MediaAsset::where('processing_status', 'failed')->count();
            if ($failed) {
                $stats[] = Stat::make('Media processing failures', $failed)->color('danger')->icon('heroicon-o-exclamation-triangle');
            }
        }

        return $stats;
    }
}
