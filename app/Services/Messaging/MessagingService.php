<?php

namespace App\Services\Messaging;

use App\Jobs\SendChannelMessage;
use App\Models\NotificationConsent;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Consent-checked, queued, idempotent SMS/WhatsApp delivery.
 */
class MessagingService
{
    /** @param array<string, MessageDriver> $drivers keyed by channel */
    public function __construct(private readonly array $drivers, private readonly array $templates = []) {}

    public function driver(string $channel): ?MessageDriver
    {
        return $this->drivers[$channel] ?? null;
    }

    public function hasConsent(string $phone, string $channel, string $purpose): bool
    {
        return NotificationConsent::query()
            ->where('contact_hash', NotificationConsent::hashContact($phone))
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->where('status', 'granted')
            ->exists();
    }

    public function render(string $template, array $params): string
    {
        $text = $this->templates[$template] ?? $template;
        foreach ($params as $k => $v) {
            $text = str_replace('{'.$k.'}', (string) $v, $text);
        }

        return $text;
    }

    /**
     * Queue a message if the recipient has opted in. Returns the log entry,
     * or null when skipped (no consent / no driver / duplicate).
     */
    public function queue(string $channel, string $phone, string $purpose, string $template, array $params, ?Model $related = null, ?string $idempotencyKey = null): ?NotificationLog
    {
        $driver = $this->driver($channel);
        if (! $driver || ! $this->hasConsent($phone, $channel, $purpose)) {
            return null;
        }

        try {
            $log = NotificationLog::create([
                'channel' => $channel,
                'provider' => $driver->name(),
                'template' => $template,
                'idempotency_key' => $idempotencyKey,
                'recipient_hash' => NotificationConsent::hashContact($phone),
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
                'status' => 'queued',
            ]);
        } catch (UniqueConstraintViolationException) {
            return null; // already queued/sent
        }

        SendChannelMessage::dispatch($log->id, encrypt($phone), $params);

        return $log;
    }
}
