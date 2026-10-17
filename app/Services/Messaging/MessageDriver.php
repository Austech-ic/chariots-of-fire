<?php

namespace App\Services\Messaging;

/** SMS or WhatsApp provider adapter. Returns the provider's message id. */
interface MessageDriver
{
    public function name(): string;

    /**
     * @param  array<string, string>  $params  template variables
     *
     * @throws MessagingException
     */
    public function send(string $to, string $template, array $params, string $body): ?string;
}
