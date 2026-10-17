<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

/** Default driver: records that a message would have been sent. */
class LogDriver implements MessageDriver
{
    public function __construct(private readonly string $channel) {}

    public function name(): string
    {
        return 'log';
    }

    public function send(string $to, string $template, array $params, string $body): ?string
    {
        Log::info("[{$this->channel}] message", ['template' => $template, 'to_suffix' => substr($to, -4)]);

        return 'log-'.uniqid();
    }
}
