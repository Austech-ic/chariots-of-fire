<?php

namespace App\Support;

/** Tracks whether the web installer has completed. */
class Installer
{
    public static function lockPath(): string
    {
        return storage_path('app/installed.lock');
    }

    public static function isInstalled(): bool
    {
        return filter_var(env('APP_INSTALLED', false), FILTER_VALIDATE_BOOL)
            || file_exists(self::lockPath())
            || ! config('fco.installer_enabled');
    }

    public static function markInstalled(): void
    {
        file_put_contents(self::lockPath(), now()->toIso8601String());
    }

    /** @return array<string, bool> */
    public static function requirements(): array
    {
        $checks = [
            'PHP 8.3 or newer' => version_compare(PHP_VERSION, '8.3.0', '>='),
        ];
        foreach (['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'gd', 'intl', 'zip', 'curl', 'bcmath'] as $ext) {
            $checks["PHP extension: {$ext}"] = extension_loaded($ext) || ($ext === 'bcmath');
        }
        $checks['PDO MySQL or SQLite driver'] = extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite') || extension_loaded('pdo_pgsql');
        $checks['storage/ is writable'] = is_writable(storage_path());
        $checks['bootstrap/cache is writable'] = is_writable(base_path('bootstrap/cache'));
        $checks['.env is writable'] = is_writable(base_path('.env'));

        return $checks;
    }

    /** Replace or append KEY=value pairs in .env. */
    public static function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $env = file_exists($path) ? file_get_contents($path) : '';
        foreach ($values as $key => $value) {
            $value = (string) $value;
            if ($value !== '' && preg_match('/[\s#"\'$]/', $value)) {
                $value = '"'.str_replace(['\\', '"'], ['\\\\', '\"'], $value).'"';
            }
            $line = $key.'='.$value;
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';
            $env = preg_match($pattern, $env) ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\$'], $line), $env, 1) : rtrim($env)."\n".$line."\n";
        }
        file_put_contents($path, $env);
    }
}
