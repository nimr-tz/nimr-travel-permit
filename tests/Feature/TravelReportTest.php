<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
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
            'report_submission_confirmed' => '1',
        ]);

        $response->assertRedirect(route('travel-requests.show', $travelRequest));
        $response->assertSessionHasNoErrors();

        $travelRequest->refresh();

        $this->assertSame('field-report.pdf', $travelRequest->travel_report_original_name);
        $this->assertSame('Key findings were documented and shared with the team.', $travelRequest->travel_report_notes);
        $this->assertNotNull($travelRequest->travel_report_submitted_at);
        $this->assertTrue($travelRequest->isTravelReportLocked());
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
                'report_submission_confirmed' => '1',
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

    public function test_uploading_report_does_not_notify_requesters_supervisor(): void
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
            'report_submission_confirmed' => '1',
        ])->assertRedirect(route('travel-requests.show', $travelRequest));

        Notification::assertNothingSent();
    }

    public function test_locked_report_cannot_be_replaced_until_system_admin_unlocks_it(): void
    {
        Storage::fake('private');

        $unit = Unit::factory()->hqStandalone()->create();
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $dg = User::factory()->directorGeneral()->create();
        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'b_departure_date' => today()->subDays(3),
            'b_return_date' => today()->subDay(),
        ]);

        $this->actingAs($requester)->post(route('travel-requests.report.upload', $travelRequest), [
            'travel_report_document' => UploadedFile::fake()->create('first.pdf', 120, 'application/pdf'),
            'report_submission_confirmed' => '1',
        ])->assertRedirect(route('travel-requests.show', $travelRequest));

        $firstPath = $travelRequest->refresh()->travel_report_document;

        $this->actingAs($requester)->post(route('travel-requests.report.upload', $travelRequest), [
            'travel_report_document' => UploadedFile::fake()->create('unapproved-replacement.pdf', 120, 'application/pdf'),
            'report_submission_confirmed' => '1',
        ])->assertForbidden();

        $this->assertSame($firstPath, $travelRequest->refresh()->travel_report_document);

        $this->actingAs($dg)
            ->patch(route('travel-requests.report.unlock', $travelRequest))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('travel-requests.report.unlock', $travelRequest))
            ->assertRedirect(route('travel-requests.show', $travelRequest));

        $this->assertNull($travelRequest->refresh()->travel_report_submitted_at);

        $this->actingAs($requester)
            ->get(route('travel-requests.create'))
            ->assertRedirect(route('travel-requests.show', $travelRequest));

        $this->actingAs($requester)->post(route('travel-requests.report.upload', $travelRequest), [
            'travel_report_document' => UploadedFile::fake()->create('corrected.pdf', 120, 'application/pdf'),
            'report_submission_confirmed' => '1',
        ])->assertRedirect(route('travel-requests.show', $travelRequest));

        $travelRequest->refresh();
        $this->assertSame('corrected.pdf', $travelRequest->travel_report_original_name);
        $this->assertTrue($travelRequest->isTravelReportLocked());
        Storage::disk('private')->assertMissing($firstPath);
        Storage::disk('private')->assertExists($travelRequest->travel_report_document);
    }

    public function test_centre_system_admin_cannot_unlock_another_centres_report(): void
    {
        Storage::fake('private');

        $firstCentre = Unit::factory()->researchCentre()->create();
        $secondCentre = Unit::factory()->researchCentre()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $firstCentre->id]);
        $requester = User::factory()->staff()->create(['unit_id' => $secondCentre->id]);
        $path = 'travel-reports/locked.pdf';
        Storage::disk('private')->put($path, 'locked report');
        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $secondCentre->id,
            'travel_report_document' => $path,
            'travel_report_original_name' => 'locked.pdf',
            'travel_report_submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('travel-requests.report.unlock', $travelRequest))
            ->assertForbidden();

        $this->assertTrue($travelRequest->refresh()->isTravelReportLocked());
    }

    public function test_original_approval_chain_member_can_view_and_download_report_without_an_action_record(): void
    {
        Storage::fake('private');

        $unit = Unit::factory()->hqStandalone()->create();
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $approver = User::factory()->manager()->create(['unit_id' => $unit->id]);
        $path = 'travel-reports/chain-visible.pdf';
        Storage::disk('private')->put($path, 'report');
        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'approval_chain' => [[
                'stage' => 'supervisor',
                'approver_id' => $approver->id,
            ]],
            'travel_report_document' => $path,
            'travel_report_original_name' => 'chain-visible.pdf',
            'travel_report_submitted_at' => now(),
        ]);

        $this->actingAs($approver)
            ->get(route('travel-requests.show', $travelRequest))
            ->assertOk();

        $this->actingAs($approver)
            ->get(route('travel-requests.report.download', $travelRequest))
            ->assertOk();
    }

    public function test_report_submission_requires_final_confirmation(): void
    {
        Storage::fake('private');

        $unit = Unit::factory()->hqStandalone()->create();
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $travelRequest = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
        ]);

        $this->actingAs($requester)->post(route('travel-requests.report.upload', $travelRequest), [
            'travel_report_document' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])->assertSessionHasErrors('report_submission_confirmed');

        $this->assertNull($travelRequest->refresh()->travel_report_document);
    }
}
