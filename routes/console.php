<?php

use Illuminate\Support\Facades\Schedule;

// Runs every morning, but a given request is only chased every 3 days: the
// command decides what is due, counting from when the request landed with its
// current approver. The requester is copied on each one.
Schedule::command('approvals:remind --days=3')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground();

// Daily report reminder at 08:30: first reminder 14 days after return, then every 3 days
Schedule::command('travel-reports:remind')
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->runInBackground();

// Not load-bearing: OverdueApprovalResolver already resolves a request the
// moment it's viewed or its owner tries to submit a new one, so this doesn't
// depend on the scheduler actually running. This is only a backstop sweep for
// a request nobody has happened to touch, if this schedule does run.
Schedule::command('travel-requests:auto-approve-overdue')
    ->dailyAt('07:30')
    ->withoutOverlapping()
    ->runInBackground();
