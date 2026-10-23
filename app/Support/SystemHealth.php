<?php

namespace App\Support;

use App\Services\Payments\PaymentManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/** Read-only checks shown on System status and the dashboard. Never exposes secrets. */
class SystemHealth
{
    /** @return list<string> */
    public static function pendingMigrations(): array
    {
        try {
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                return ['(migrations table missing)'];
            }
            $files = $migrator->getMigrationFiles([database_path('migrations')]);

            return array_values(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
        } catch (Throwable) {
            return [];
        }
    }

    public static function schedulerLastRun(): ?Carbon
    {
        $value = Cache::get('fco.scheduler.last_run');

        return $value ? Carbon::parse($value) : null;
    }

    public static function schedulerHealthy(): bool
    {
        $last = self::schedulerLastRun();

        return $last !== null && $last->gt(now()->subMinutes(10));
    }

    /** @return array{pending:int, failed:int, oldest:?Carbon} */
    public static function queue(): array
    {
        try {
            $oldest = DB::table('jobs')->min('created_at');

            return [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
                'oldest' => $oldest ? Carbon::createFromTimestamp($oldest) : null,
            ];
        } catch (Throwable) {
            return ['pending' => 0, 'failed' => 0, 'oldest' => null];
        }
    }

    public static function lastBackup(): ?Carbon
    {
        $files = File::glob(storage_path('app/backups/fco-backup-*.zip'));

        return $files ? Carbon::createFromTimestamp(max(array_map('filemtime', $files))) : null;
    }

    /**
     * Each check: [label, ok (bool|null for "optional/not set"), detail].
     *
     * @return list<array{0:string,1:?bool,2:string}>
     */
    public static function checks(): array
    {
        $queue = self::queue();
        $last = self::schedulerLastRun();
        $pending = self::pendingMigrations();
        $payments = app(PaymentManager::class);
        $paymentOptions = $payments->options();
        $mailer = config('mail.default');
        $mailHost = config("mail.mailers.{$mailer}.host");
        $backup = self::lastBackup();

        return [
            ['Database up to date', $pending === [], $pending === [] ? 'No pending updates' : count($pending).' update(s) waiting — click “Run database updates”'],
            ['Cron job (scheduler)', self::schedulerHealthy(), $last ? 'Last ran '.$last->diffForHumans() : 'Has not run yet — add the cron job from docs/SHARED_HOSTING.md'],
            ['Background queue', $queue['pending'] < 50 && (! $queue['oldest'] || $queue['oldest']->gt(now()->subMinutes(15))),
                $queue['pending'].' waiting, '.$queue['failed'].' failed'.($queue['oldest'] ? ' · oldest '.$queue['oldest']->diffForHumans() : '')],
            ['Email sending', in_array($mailer, ['log', 'array'], true) ? false : (filled($mailHost) || $mailer !== 'smtp'),
                in_array($mailer, ['log', 'array'], true) ? 'MAIL_MAILER is “'.$mailer.'” — emails are not delivered' : strtoupper($mailer).($mailHost ? ' via '.$mailHost : '').' · from '.config('mail.from.address')],
            ['Online giving', isset($paymentOptions[config('fco.payments.default')]) && config('fco.payments.default') !== 'test',
                $paymentOptions ? 'Active: '.implode(', ', $paymentOptions).' (default '.$payments->defaultKey().')' : 'No payment provider configured'],
            ['HTTPS', str_starts_with((string) config('app.url'), 'https://'), (string) config('app.url')],
            ['Debug mode off', ! config('app.debug'), config('app.debug') ? 'APP_DEBUG=true exposes error details — set it to false' : 'OK'],
            ['Backups', $backup !== null && $backup->gt(now()->subDays(2)) ? (filled(config('fco.backup.password'))) : false,
                ($backup ? 'Last backup '.$backup->diffForHumans() : 'No backup yet').(filled(config('fco.backup.password')) ? ' · encrypted' : ' · set BACKUP_PASSWORD to encrypt')],
            ['Staff two-factor required', config('fco.require_staff_mfa') ? true : null, config('fco.require_staff_mfa') ? 'Enforced' : 'Optional (set REQUIRE_STAFF_MFA=true to enforce)'],
            ['YouTube live detection', filled(config('fco.streaming.youtube_api_key')) ? true : null, filled(config('fco.streaming.youtube_api_key')) ? 'API key set' : 'Optional — live status is set manually'],
            ['SMS / WhatsApp', in_array(config('fco.messaging.sms_driver'), ['termii', 'twilio'], true) || in_array(config('fco.messaging.whatsapp_driver'), ['whatsapp_cloud', 'twilio'], true) ? true : null,
                'SMS: '.config('fco.messaging.sms_driver').' · WhatsApp: '.config('fco.messaging.whatsapp_driver')],
            ['Newsletter provider sync', config('fco.newsletter.provider') !== 'none' ? true : null, config('fco.newsletter.provider') === 'none' ? 'Optional — consent is stored on this site' : ucfirst(config('fco.newsletter.provider'))],
        ];
    }

    /** @return array<string, string> */
    public static function environment(): array
    {
        return [
            'Application version' => config('fco.version'),
            'PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Database' => DB::getDriverName(),
            'Upload limit (PHP)' => ini_get('upload_max_filesize').' per file, '.ini_get('post_max_size').' per form',
            'Memory limit' => (string) ini_get('memory_limit'),
            'Time zone shown to visitors' => config('app.display_timezone'),
        ];
    }
}
