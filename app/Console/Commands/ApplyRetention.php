<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\ContactEnquiry;
use App\Models\CounsellingRequest;
use App\Models\NotificationLog;
use App\Models\WebhookEvent;
use App\Support\Audit;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

/**
 * Applies the data-retention schedule (configure periods in config/fco.php
 * after review with the ministry's data protection adviser).
 */
#[AsCommand(name: 'fco:apply-retention', description: 'Redact or delete personal data past its retention period')]
class ApplyRetention extends Command
{
    protected $signature = 'fco:apply-retention';

    public function handle(): int
    {
        $r = config('fco.retention');

        $redacted = 0;
        CounsellingRequest::query()->where('status', 'closed')->where('closed_at', '<', now()->subDays($r['counselling_closed_days']))
            ->each(function (CounsellingRequest $request) use (&$redacted) {
                if (($request->contact_data['redacted'] ?? false) === true) {
                    return;
                }
                $request->update([
                    'contact_data' => ['redacted' => true],
                    'encrypted_message' => '[redacted under retention policy]',
                    'preferred_times' => null,
                    'internal_notes' => null,
                ]);
                $redacted++;
            });

        Appointment::query()->whereIn('status', ['completed', 'cancelled', 'no_show'])
            ->where('ends_at', '<', now()->subDays($r['appointment_contact_days']))
            ->each(function (Appointment $a) {
                if (($a->contact_data['redacted'] ?? false) !== true) {
                    $a->update(['contact_data' => ['redacted' => true]]);
                }
            });

        $enquiries = ContactEnquiry::query()->whereIn('status', ['resolved', 'spam'])->where('updated_at', '<', now()->subDays($r['enquiry_days']))->delete();
        WebhookEvent::query()->where('received_at', '<', now()->subDays($r['webhook_days']))->delete();
        NotificationLog::query()->where('created_at', '<', now()->subDays($r['notification_log_days']))->delete();

        Audit::log('retention_applied', null, ['counselling_redacted' => $redacted, 'enquiries_deleted' => $enquiries]);
        $this->info("Redacted {$redacted} counselling request(s); deleted {$enquiries} enquiry(ies).");

        return self::SUCCESS;
    }
}
