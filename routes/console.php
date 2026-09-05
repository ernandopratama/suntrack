<?php

use App\Jobs\CleanTemporaryFilesJob;
use App\Jobs\MonitorExpiredLinksJob;
use App\Jobs\SendApprovalReminderJob;
use App\Jobs\SendDeadlineReminderJob;
use App\Jobs\SendTaskPriorityReminderJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ------------------------------------------
// SunTrack Automated Scheduler Tasks (Sprint 8)
// ------------------------------------------

// Daily Brand approval reminders at 08:00 AM
Schedule::job(new SendApprovalReminderJob)->dailyAt('08:00')->name('suntrack:remind-approvals')->withoutOverlapping();

// Daily Deadline alerts at 09:00 AM
Schedule::job(new SendDeadlineReminderJob)->dailyAt('09:00')->name('suntrack:remind-deadlines')->withoutOverlapping();

Schedule::job(new SendTaskPriorityReminderJob)->everyFifteenMinutes()->name('suntrack:remind-priority-tasks')->withoutOverlapping();

// Hourly monitoring of expired secure public links
Schedule::job(new MonitorExpiredLinksJob)->hourly()->name('suntrack:monitor-links')->withoutOverlapping();

// Daily maintenance to clean temporary export files older than 24 hours
Schedule::job(new CleanTemporaryFilesJob)->dailyAt('02:00')->name('suntrack:clean-temp')->withoutOverlapping();

// Daily automated database backup at 01:00 AM (Sprint 9)
Schedule::command('suntrack:backup-db')->dailyAt('01:00')->name('suntrack:backup-db')->withoutOverlapping();
