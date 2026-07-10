<?php

namespace App\Console\Commands;

use App\Models\TravelRequest;
use App\Notifications\TravelReportReminderNotification;
use Illuminate\Console\Command;

class SendTravelReportReminders extends Command
{
    protected $signature = 'travel-reports:remind
        {--first-after=14 : Days after trip return date before the first reminder}
        {--repeat-after=3 : Days between repeat reminders}';

    protected $description = 'Remind travellers to upload missing reports for approved trips';

    public function handle(): int
    {
        $firstAfter = max(1, (int) $this->option('first-after'));
        $repeatAfter = max(1, (int) $this->option('repeat-after'));

        $firstReminderCutoff = now()->subDays($firstAfter)->toDateString();
        $repeatReminderCutoff = now()->subDays($repeatAfter);

        $query = TravelRequest::with(['requester', 'unit'])
            ->where('status', TravelRequest::STATUS_APPROVED)
            ->whereNull('travel_report_document')
            ->whereNotNull('b_return_date')
            ->whereDate('b_return_date', '<=', $firstReminderCutoff)
            ->where(function ($query) use ($repeatReminderCutoff) {
                $query->whereNull('travel_report_last_reminded_at')
                    ->orWhere('travel_report_last_reminded_at', '<=', $repeatReminderCutoff);
            });

        $sent = 0;
        $skipped = 0;

        $query->chunkById(100, function ($travelRequests) use (&$sent, &$skipped) {
            foreach ($travelRequests as $travelRequest) {
                $requester = $travelRequest->requester;

                if (!$requester || !$requester->is_active) {
                    $skipped++;
                    continue;
                }

                try {
                    $requester->notify(new TravelReportReminderNotification($travelRequest));

                    $travelRequest->forceFill([
                        'travel_report_last_reminded_at' => now(),
                        'travel_report_reminder_count' => ((int) $travelRequest->travel_report_reminder_count) + 1,
                    ])->save();

                    $sent++;
                    $this->line("  Reminded {$requester->name} about missing report for {$travelRequest->request_number}");
                } catch (\Throwable $e) {
                    $this->warn("  Failed to remind {$requester->name} for {$travelRequest->request_number}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Done. Sent {$sent} travel report reminder(s); skipped {$skipped} inactive or missing requester(s).");

        return self::SUCCESS;
    }
}
