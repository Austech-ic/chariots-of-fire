<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

/** Termii SMS (https://developers.termii.com). Widely used in Nigeria. */
class TermiiSmsDriver implements MessageDriver
{
    public function __construct(private readonly ?string $apiKey, private readonly ?string $senderId, private readonly string $baseUrl = 'https://api.ng.termii.com') {}

    public function name(): string
    {
        return 'termii';
    }

    public function send(string $to, string $template, array $params, string $body): ?string
    {
        if (! $this->apiKey || ! $this->senderId) {
            throw new MessagingException('Termii is not configured.');
        }

        $response = Http::timeout(15)->post($this->baseUrl.'/api/sms/send', [
            'api_key' => $this->apiKey,
            'to' => ltrim($to, '+'),
            'from' => $this->senderId,
            'sms' => $body,
            'type' => 'plain',
            'channel' => 'generic',
        ]);

        if (! $response->successful()) {
            throw new MessagingException('Termii error '.$response->status());
        }

        return $response->json('message_id');
    }
}
