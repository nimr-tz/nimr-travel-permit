<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\TravelReportSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TravelReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_can_upload_report_for_approved_travel_request(): void
    {
        Storage::fake('private');

        $unit = Unit::factory()->hqStandalone()->create();
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
        ]);

        $file = UploadedFile::fake()->create('field-report.pdf', 120, 'application/pdf');

        $response = $this->actingAs($requester)->post(route('travel-requests.report.upload', $travelRequest), [
            'travel_report_document' => $file,
            'travel_report_notes' => 'Key findings were documented and shared with the team.',
        ]);

        $response->assertRedirect(route('travel-requests.show', $travelRequest));
        $response->assertSessionHasNoErrors();

        $travelRequest->refresh();

        $this->assertSame('field-report.pdf', $travelRequest->travel_report_original_name);
        $this->assertSame('Key findings were documented and shared with the team.', $travelRequest->travel_report_notes);
        $this->assertNotNull($travelRequest->travel_report_submitted_at);
        Storage::disk('private')->assertExists($travelRequest->travel_report_document);
    }

    public function test_requester_cannot_upload_report_before_request_is_approved(): void
    {
        Storage::fake('private');

        $unit = Unit::factory()->hqStandalone()->create();
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $travelRequest = TravelRequest::factory()->pending()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
        ]);

        $file = UploadedFile::fake()->create('field-report.pdf', 120, 'application/pdf');

        $this->actingAs($requester)
            ->post(route('travel-requests.report.upload', $travelRequest), [
                'travel_report_document' => $file,
            ])
            ->assertForbidden();

        $this->assertNull($travelRequest->refresh()->travel_report_document);
    }

    public function test_authorized_viewer_can_download_submitted_travel_report(): void
    {
        Storage::fake('private');

        $unit = Unit::factory()->hqStandalone()->create();
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $dg = User::factory()->directorGeneral()->create();
        $path = 'travel-reports/report.pdf';
        Storage::disk('private')->put($path, 'report contents');

        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'travel_report_document' => $path,
            'travel_report_original_name' => 'field-report.pdf',
            'travel_report_submitted_at' => now(),
        ]);

        $this->actingAs($dg)
            ->get(route('travel-requests.report.download', $travelRequest))
            ->assertOk();
    }

    public function test_uploading_report_notifies_requesters_supervisor(): void
    {
        Notification::fake();
        Storage::fake('private');

        $unit = Unit::factory()->hqStandalone()->create();
        $supervisor = User::factory()->manager()->create(['unit_id' => $unit->id]);
        $requester = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => $supervisor->id,
        ]);
        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
        ]);

        $file = UploadedFile::fake()->create('field-report.pdf', 120, 'application/pdf');

        $this->actingAs($requester)->post(route('travel-requests.report.upload', $travelRequest), [
            'travel_report_document' => $file,
        ])->assertRedirect(route('travel-requests.show', $travelRequest));

        Notification::assertSentTo($supervisor, TravelReportSubmittedNotification::class);
    }
}
