<?php

namespace App\Console\Commands;

use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TravelRequestReminderNotification;
use Illuminate\Console\Command;

class NotifyPendingApprovers extends Command
{
    protected $signature   = 'approvals:remind {--days=3 : Minimum days a request must be waiting before a reminder is sent}';
    protected $description = 'Send reminder emails to approvers who have pending travel requests older than --days days';

    public function handle(): int
    {
        $threshold = max(1, (int) $this->option('days'));
        $cutoff    = now()->subDays($threshold);

        $pending = TravelRequest::with(['currentApprover'])
            ->where('status', TravelRequest::STATUS_PENDING)
            ->whereNotNull('current_approver_id')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '<=', $cutoff)
            ->get();

        if ($pending->isEmpty()) {
            $this->info("No pending requests older than {$threshold} day(s). Nothing to do.");
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($pending as $tr) {
            $approver = $tr->currentApprover;
            if (!$approver || !$approver->is_active) {
                continue;
            }

            $daysWaiting = (int) $tr->submitted_at->diffInDays(now());

            try {
                $approver->notify(new TravelRequestReminderNotification($tr, $daysWaiting));
                $sent++;
                $this->line("  Reminded {$approver->name} about {$tr->request_number} ({$daysWaiting}d waiting)");
            } catch (\Throwable $e) {
                $this->warn("  Failed to remind {$approver->name} for {$tr->request_number}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Sent {$sent} reminder(s) for requests pending ≥ {$threshold} day(s).");

        return self::SUCCESS;
    }
}
