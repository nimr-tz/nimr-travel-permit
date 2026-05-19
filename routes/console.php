<?php

use Illuminate\Support\Facades\Schedule;

// Daily reminder at 08:00 — notify approvers about requests pending ≥ 3 days
Schedule::command('approvals:remind --days=3')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();
