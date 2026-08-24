<?php

use App\Models\ActivityLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune activity logs older than ActivityLog::PRUNE_AFTER_DAYS every day.
// Requires something to actually invoke `php artisan schedule:run` once a
// minute in production — see the deployment notes for what that needs on
// Railway, since a normal web container doesn't run cron on its own.
Schedule::command('model:prune', ['--model' => [ActivityLog::class]])->daily();
