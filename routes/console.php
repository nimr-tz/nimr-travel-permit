<?php

use Illuminate\Support\Facades\Schedule;

// Daily reminder at 08:00 — notify approvers about requests pending ≥ 3 days
Schedule::command('approvals:remind --days=3')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

// Daily report reminder at 08:30: first reminder 14 days after return, then every 3 days
Schedule::command('travel-reports:remind')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->runInBackground();
