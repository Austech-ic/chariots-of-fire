<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

#[AsCommand(name: 'fco:restore', description: 'Restore a backup created by fco:backup (replaces all current data)')]
class Restore extends Command
{
    protected $signature = 'fco:restore {file : Path to fco-backup-*.zip} {--force : Do not ask for confirmation}';

    public function handle(): int
    {
        $file = $this->argument('file');
        $zip = new ZipArchive;
        if (! is_file($file) || $zip->open($file) !== true) {
            $this->error('Cannot open backup archive.');

            return self::FAILURE;
        }
        if ($password = config('fco.backup.password')) {
            $zip->setPassword($password);
        }

        $sql = $zip->getFromName('database.sql');
        if ($sql === false) {
            $this->error('database.sql not found or wrong BACKUP_PASSWORD.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This REPLACES all data in '.DB::getDatabaseName().'. Continue?')) {
            return self::FAILURE;
        }

        Artisan::call('down', ['--retry' => 60]);
        try {
            Artisan::call('migrate', ['--force' => true]);
            DB::unprepared($sql);

            $mediaRoot = storage_path('app/media');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'media/') && ! str_contains($name, '..')) {
                    $target = $mediaRoot.'/'.substr($name, 6);
                    File::ensureDirectoryExists(dirname($target));
                    File::put($target, $zip->getFromIndex($i));
                }
            }
        } finally {
            $zip->close();
            Artisan::call('optimize:clear');
            Artisan::call('up');
        }

        $this->info('Restore complete.');

        return self::SUCCESS;
    }
}
