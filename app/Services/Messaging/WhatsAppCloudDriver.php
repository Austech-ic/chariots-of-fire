<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

/**
 * WhatsApp Business Platform (Cloud API). Business-initiated messages must
 * use templates pre-approved by Meta; template names map to config.
 */
class WhatsAppCloudDriver implements MessageDriver
{
    public function __construct(
        private readonly ?string $token,
        private readonly ?string $phoneNumberId,
        private readonly array $templates = [],
        private readonly string $version = 'v21.0',
    ) {}

    public function name(): string
    {
        return 'whatsapp_cloud';
    }

    public function send(string $to, string $template, array $params, string $body): ?string
    {
        if (! $this->token || ! $this->phoneNumberId) {
            throw new MessagingException('WhatsApp Cloud API is not configured.');
        }
        $approved = $this->templates[$template] ?? null;
        if (! $approved) {
            throw new MessagingException("No approved WhatsApp template mapped for [{$template}].");
        }

        $response = Http::withToken($this->token)->timeout(15)
            ->post("https://graph.facebook.com/{$this->version}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($to, '+'),
                'type' => 'template',
                'template' => [
                    'name' => $approved['name'],
                    'language' => ['code' => $approved['language'] ?? 'en'],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(fn ($v) => ['type' => 'text', 'text' => (string) $v], array_values($params)),
                    ]],
                ],
            ]);

        if (! $response->successful()) {
            throw new MessagingException('WhatsApp error '.$response->status());
        }

        return $response->json('messages.0.id');
    }
}
