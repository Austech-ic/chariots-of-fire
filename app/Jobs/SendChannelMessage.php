<?php

namespace App\Jobs;

use App\Models\NotificationConsent;
use App\Models\NotificationLog;
use App\Services\Messaging\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class SendChannelMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 4;

    public array $backoff = [60, 300, 1800];

    public function __construct(public int $logId, public string $encryptedPhone, public array $params) {}

    public function handle(MessagingService $messaging): void
    {
        $log = NotificationLog::find($this->logId);
        if (! $log || in_array($log->status, ['sent', 'delivered', 'skipped'], true)) {
            return;
        }

        $phone = decrypt($this->encryptedPhone);

        // Opt-outs take effect immediately, even for messages already queued.
        $stillConsented = NotificationConsent::query()
            ->where('contact_hash', NotificationConsent::hashContact($phone))
            ->where('channel', $log->channel)->where('status', 'granted')->exists();
        if (! $stillConsented) {
            $log->update(['status' => 'skipped', 'error' => 'Consent withdrawn']);

            return;
        }

        $log->increment('attempts');
        try {
            $id = $messaging->driver($log->channel)?->send($phone, $log->template, $this->params, $messaging->render($log->template, $this->params));
            $log->update(['status' => 'sent', 'provider_message_id' => $id, 'sent_at' => now(), 'error' => null]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);

            throw $e;
        }
    }
}
