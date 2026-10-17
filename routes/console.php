<?php

use Illuminate\Support\Facades\Schedule;

/*
| Shared hosting needs only ONE cron entry:
|   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
| The queue is processed by the scheduler itself (no long-running worker).
*/
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()->withoutOverlapping(5)->when(fn () => config('queue.default') !== 'sync');

Schedule::command('fco:publish-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('fco:sync-streams')->everyTwoMinutes()->withoutOverlapping();
Schedule::command('fco:reconcile-donations')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('fco:appointment-reminders')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('fco:generate-occurrences')->dailyAt('01:10');
Schedule::command('fco:apply-retention')->dailyAt('02:20');
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::command('auth:clear-resets')->everyFifteenMinutes();
Schedule::command('fco:backup')->dailyAt('03:15')->withoutOverlapping();
