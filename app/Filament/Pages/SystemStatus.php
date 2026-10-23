<?php

namespace App\Filament\Pages;

use App\Support\Audit;
use App\Support\SystemHealth;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Throwable;
use UnitEnum;

/**
 * Health checks and maintenance actions for hosts without SSH access:
 * apply database updates after uploading a new release, clear caches,
 * process the queue and send a test email.
 */
class SystemStatus extends Page
{
    protected string $view = 'filament.pages.system-status';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'System status';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasPermission('settings.manage');
    }

    public static function getNavigationBadge(): ?string
    {
        $issues = (SystemHealth::pendingMigrations() !== [] ? 1 : 0) + (SystemHealth::schedulerHealthy() ? 0 : 1);

        return $issues ? (string) $issues : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected function getViewData(): array
    {
        return [
            'checks' => SystemHealth::checks(),
            'environment' => SystemHealth::environment(),
            'pending' => SystemHealth::pendingMigrations(),
        ];
    }

    private static function superAdmin(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('migrate')->label('Run database updates')->icon(Heroicon::OutlinedCircleStack)->color('danger')
                ->visible(fn () => self::superAdmin() && SystemHealth::pendingMigrations() !== [])
                ->requiresConfirmation()
                ->modalDescription('Applies the database changes included in the uploaded release. Take a backup first if you have not done so today.')
                ->action(function () {
                    try {
                        Artisan::call('migrate', ['--force' => true]);
                        Artisan::call('optimize:clear');
                        Audit::log('database_updated', null, ['version' => config('fco.version')]);
                        Notification::make()->success()->title('Database updated')->send();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Update failed')->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('backup')->label('Back up now')->icon(Heroicon::OutlinedArchiveBox)->color('gray')
                ->visible(fn () => self::superAdmin())
                ->action(function () {
                    @set_time_limit(300);
                    $code = Artisan::call('fco:backup');
                    Audit::log('backup_created');
                    Notification::make()->{$code === 0 ? 'success' : 'danger'}()->title($code === 0 ? 'Backup created' : 'Backup failed')
                        ->body(trim(Artisan::output()))->send();
                }),
            Action::make('clearCache')->label('Clear caches')->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Use this after uploading a new release or editing .env.')
                ->action(function () {
                    Artisan::call('optimize:clear');
                    Audit::log('caches_cleared');
                    Notification::make()->success()->title('Caches cleared')->send();
                }),
            Action::make('queue')->label('Process queue now')->icon(Heroicon::OutlinedPlay)->color('gray')
                ->action(function () {
                    @set_time_limit(120);
                    Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 50, '--tries' => 3]);
                    Notification::make()->success()->title('Queue processed')->send();
                }),
            Action::make('testEmail')->label('Send test email')->icon(Heroicon::OutlinedEnvelope)->color('gray')
                ->action(function () {
                    $to = auth()->user()->email;
                    try {
                        Mail::raw('This is a test email from '.config('app.name').'. Email delivery is working.', fn ($m) => $m->to($to)->subject('Test email'));
                        Notification::make()->success()->title('Test email sent to '.$to)->body('If it does not arrive within a few minutes, check the spam folder and the SMTP settings in .env.')->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Email could not be sent')->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }
}
