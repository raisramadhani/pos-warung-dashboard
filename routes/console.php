<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('activitylog:clean')
    ->daily()
    ->withoutOverlapping()
    ->evenInMaintenanceMode()
    ->runInBackground()
    ->description('Clean old activity log records');

Schedule::command('model:prune')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Prune old model records');
