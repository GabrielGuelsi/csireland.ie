<?php

use App\Jobs\ProcessScheduledMessagesJob;
use App\Jobs\SendDailyAlertsJob;
use App\Jobs\SendMorningDigestJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new SendDailyAlertsJob)->dailyAt('07:00');
Schedule::job(new ProcessScheduledMessagesJob)->dailyAt('07:00');
Schedule::job(new SendMorningDigestJob)->dailyAt('08:30');
