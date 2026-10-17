<?php

// Behind Cloudflare / a load balancer set TRUSTED_PROXIES="*" (or a comma-separated IP list)
// so client IPs (rate limiting, audit log) and HTTPS detection are correct.
$proxies = env('TRUSTED_PROXIES');

return [
    'proxies' => $proxies === '*' ? '*' : ($proxies ? array_map('trim', explode(',', $proxies)) : null),
];
