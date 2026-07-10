<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\TravelReportReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TravelReportReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_report_reminder_is_sent_two_weeks_after_trip_return(): void
    {
        Notification::fake();

        [$requester, $travelRequest] = $this->approvedTravelRequest(returnedDaysAgo: 14);

        $this->artisan('travel-reports:remind')
            ->assertExitCode(0);

        Notification::assertSentTo($requester, TravelReportReminderNotification::class);

        $travelRequest->refresh();
        $this->assertNotNull($travelRequest->travel_report_last_reminded_at);
        $this->assertSame(1, $travelRequest->travel_report_reminder_count);
    }

    public function test_missing_report_reminder_is_not_sent_before_two_weeks_after_trip_return(): void
    {
        Notification::fake();

        [$requester, $travelRequest] = $this->approvedTravelRequest(returnedDaysAgo: 13);

        $this->artisan('travel-reports:remind')
            ->assertExitCode(0);

        Notification::assertNotSentTo($requester, TravelReportReminderNotification::class);
        $this->assertNull($travelRequest->refresh()->travel_report_last_reminded_at);
    }

    public function test_missing_report_reminder_repeats_after_three_days(): void
    {
        Notification::fake();

        [$requester, $travelRequest] = $this->approvedTravelRequest(returnedDaysAgo: 14, overrides: [
            'travel_report_last_reminded_at' => now()->subDays(3)->subMinute(),
            'travel_report_reminder_count' => 1,
        ]);

        $this->artisan('travel-reports:remind')
            ->assertExitCode(0);

        Notification::assertSentTo($requester, TravelReportReminderNotification::class);

        $travelRequest->refresh();
        $this->assertSame(2, $travelRequest->travel_report_reminder_count);
    }

    public function test_missing_report_reminder_does_not_repeat_before_three_days(): void
    {
        Notification::fake();

        [$requester, $travelRequest] = $this->approvedTravelRequest(returnedDaysAgo: 14, overrides: [
            'travel_report_last_reminded_at' => now()->subDays(2),
            'travel_report_reminder_count' => 1,
        ]);

        $this->artisan('travel-reports:remind')
            ->assertExitCode(0);

        Notification::assertNotSentTo($requester, TravelReportReminderNotification::class);
        $this->assertSame(1, $travelRequest->refresh()->travel_report_reminder_count);
    }

    public function test_missing_report_reminder_skips_requests_with_uploaded_reports(): void
    {
        Notification::fake();

        [$requester, $travelRequest] = $this->approvedTravelRequest(returnedDaysAgo: 14, overrides: [
            'travel_report_document' => 'travel-reports/report.pdf',
            'travel_report_original_name' => 'report.pdf',
            'travel_report_submitted_at' => now(),
        ]);

        $this->artisan('travel-reports:remind')
            ->assertExitCode(0);

        Notification::assertNotSentTo($requester, TravelReportReminderNotification::class);
        $this->assertNull($travelRequest->refresh()->travel_report_last_reminded_at);
    }

    private function approvedTravelRequest(int $returnedDaysAgo, array $overrides = []): array
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);

        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'b_return_date' => now()->subDays($returnedDaysAgo)->toDateString(),
            ...$overrides,
        ]);

        return [$requester, $travelRequest];
    }
}
