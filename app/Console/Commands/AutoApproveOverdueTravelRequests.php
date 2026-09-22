<?php

namespace App\Console\Commands;

use App\Services\OverdueApprovalResolver;
use Illuminate\Console\Command;

/**
 * A manual sweep for an admin to run on demand. Not required for correctness:
 * OverdueApprovalResolver already resolves a request the moment it's viewed
 * or the moment its owner tries to submit a new one, with no schedule needed.
 * This just catches anything nobody has happened to touch yet.
 */
class AutoApproveOverdueTravelRequests extends Command
{
    protected $signature = 'travel-requests:auto-approve-overdue';

    protected $description = 'Auto-approve requests still pending at the final approver after their return date has passed';

    public function handle(OverdueApprovalResolver $resolver): int
    {
        $count = $resolver->resolveAll();

        $count > 0
            ? $this->info("Auto-approved {$count} overdue request(s).")
            : $this->info('No overdue requests waiting on the final approver.');

        return self::SUCCESS;
    }
}
