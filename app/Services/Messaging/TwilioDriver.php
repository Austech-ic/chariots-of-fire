<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

/** Twilio Programmable Messaging for SMS or WhatsApp (sender prefixed "whatsapp:"). */
class TwilioDriver implements MessageDriver
{
    public function __construct(
        private readonly ?string $sid,
        private readonly ?string $token,
        private readonly ?string $from,
        private readonly bool $whatsapp = false,
    ) {}

    public function name(): string
    {
        return 'twilio';
    }

    public function send(string $to, string $template, array $params, string $body): ?string
    {
        if (! $this->sid || ! $this->token || ! $this->from) {
            throw new MessagingException('Twilio is not configured.');
        }

        $prefix = $this->whatsapp ? 'whatsapp:' : '';
        $response = Http::asForm()->withBasicAuth($this->sid, $this->token)->timeout(15)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                'To' => $prefix.$to,
                'From' => $prefix.$this->from,
                'Body' => $body,
            ]);

        if (! $response->successful()) {
            throw new MessagingException('Twilio error '.$response->status());
        }

        return $response->json('sid');
    }
}
