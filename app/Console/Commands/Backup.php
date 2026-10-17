<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Portable backup that needs no shell tools (mysqldump is often unavailable on
 * shared hosting). Produces a data-only SQL file — restore it onto a freshly
 * migrated database with `php artisan fco:restore` or phpMyAdmin — plus,
 * optionally, the uploaded media. Encrypted with AES-256 when BACKUP_PASSWORD is set.
 */
#[AsCommand(name: 'fco:backup', description: 'Create an (encrypted) backup of the database and optionally media')]
class Backup extends Command
{
    protected $signature = 'fco:backup {--media : Include uploaded media files} {--no-prune : Keep all old backups}';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0700);

        $stamp = now()->format('Ymd-His');
        $sqlPath = "{$dir}/database-{$stamp}.sql";
        $tables = $this->writeSql($sqlPath);

        $zipPath = "{$dir}/fco-backup-{$stamp}.zip";
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Could not create the backup archive.');

            return self::FAILURE;
        }

        $password = config('fco.backup.password');
        $add = function (string $file, string $name) use ($zip, $password) {
            $zip->addFile($file, $name);
            if ($password) {
                $zip->setEncryptionName($name, ZipArchive::EM_AES_256, $password);
            }
        };

        if ($password) {
            $zip->setPassword($password);
        }
        $add($sqlPath, 'database.sql');
        $zip->addFromString('manifest.json', json_encode([
            'created_at' => now()->toIso8601String(),
            'app_url' => config('app.url'),
            'driver' => DB::getDriverName(),
            'tables' => $tables,
            'migrations' => DB::table('migrations')->orderBy('id')->pluck('migration'),
            'encrypted' => (bool) $password,
        ], JSON_PRETTY_PRINT));

        $media = storage_path('app/media');
        if (($this->option('media') || config('fco.backup.include_media')) && is_dir($media)) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($media, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $add($file->getPathname(), 'media/'.ltrim(substr($file->getPathname(), strlen($media)), '/'));
            }
        }

        $zip->close();
        File::delete($sqlPath);
        @chmod($zipPath, 0600);

        if (! $this->option('no-prune')) {
            collect(File::glob("{$dir}/fco-backup-*.zip"))->sort()->reverse()->slice(config('fco.backup.keep'))->each(fn ($old) => File::delete($old));
        }

        $this->info('Backup written to '.$zipPath.($password ? ' (encrypted)' : ' (NOT encrypted — set BACKUP_PASSWORD)'));

        return self::SUCCESS;
    }

    /** @return array<string, int> row counts per table */
    private function writeSql(string $path): array
    {
        $driver = DB::getDriverName();
        $pdo = DB::getPdo();
        $grammar = DB::getQueryGrammar();
        $exclude = config('fco.backup.exclude_tables');
        $tables = collect(Schema::getTableListing(schemaQualified: false))
            ->reject(fn ($t) => in_array($t, $exclude, true) || str_starts_with($t, 'sqlite_'))->values();

        $out = fopen($path, 'w');
        fwrite($out, "-- Flaming Chariot Outreach data backup (".now()->toIso8601String().")\n-- Restore onto a freshly migrated database of the same version.\n");
        fwrite($out, match ($driver) {
            'mysql', 'mariadb' => "SET FOREIGN_KEY_CHECKS=0;\n",
            'pgsql' => "SET session_replication_role = replica;\n",
            'sqlite' => "PRAGMA foreign_keys=OFF;\n",
            default => '',
        });

        foreach ($tables->reverse() as $table) {
            fwrite($out, 'DELETE FROM '.$grammar->wrapTable($table).";\n");
        }

        $counts = [];
        foreach ($tables as $table) {
            $count = 0;
            $columns = null;
            foreach (DB::table($table)->cursor() as $row) {
                $row = (array) $row;
                $columns ??= implode(', ', array_map(fn ($c) => $grammar->wrap($c), array_keys($row)));
                $values = implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : (is_bool($v) ? (int) $v : $pdo->quote((string) $v)), $row));
                fwrite($out, 'INSERT INTO '.$grammar->wrapTable($table)." ({$columns}) VALUES ({$values});\n");
                $count++;
            }
            $counts[$table] = $count;
        }

        fwrite($out, match ($driver) {
            'mysql', 'mariadb' => "SET FOREIGN_KEY_CHECKS=1;\n",
            'pgsql' => "SET session_replication_role = DEFAULT;\n",
            'sqlite' => "PRAGMA foreign_keys=ON;\n",
            default => '',
        });
        fclose($out);

        return $counts;
    }
}
