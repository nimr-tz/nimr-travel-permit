<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression cover for the travel request workflow defects found in the
 * pre-release audit: forged traveller identity, lost handover documents,
 * stale-tab overwrites, transfers, and permit numbering.
 */
class TravelRequestIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $dg;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('private');

        $this->dg = User::factory()->directorGeneral()->create(['unit_id' => null]);
    }

    public function test_traveller_identity_is_taken_from_the_account_not_the_form(): void
    {
        [$staff] = $this->centreWithStaff();

        $this->actingAs($staff)
            ->post(route('travel-requests.store'), $this->payload($staff, [
                'b_applicant_name' => 'Director General',
                'b_email' => 'dg@nimr.or.tz',
                'b_position' => 'Director General',
            ]))
            ->assertRedirect();

        $travelRequest = TravelRequest::where('requester_id', $staff->id)->firstOrFail();

        $this->assertSame($staff->name, $travelRequest->b_applicant_name);
        $this->assertSame($staff->email, $travelRequest->b_email);
        $this->assertNotSame('Director General', $travelRequest->b_applicant_name);
    }

    public function test_returned_request_resubmits_without_re_uploading_the_handover_document(): void
    {
        [$staff, $supervisor] = $this->centreWithStaff();

        $travelRequest = $this->submit($staff);
        $originalDocument = $travelRequest->g_handover_document;
        $this->assertNotNull($originalDocument);

        $this->returnToRequester($travelRequest, $supervisor);

        // Resubmit with no file part at all — the stored PDF must satisfy it.
        $payload = $this->payload($staff);
        unset($payload['g_handover_document']);

        $this->actingAs($staff)
            ->patch(route('travel-requests.update', $travelRequest), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $travelRequest->refresh();

        $this->assertSame(TravelRequest::STATUS_PENDING, $travelRequest->status);
        $this->assertSame($originalDocument, $travelRequest->g_handover_document);
        Storage::disk('private')->assertExists($originalDocument);
    }

    public function test_replacing_the_handover_document_keeps_the_old_file_until_the_write_succeeds(): void
    {
        [$staff, $supervisor] = $this->centreWithStaff();

        $travelRequest = $this->submit($staff);
        $originalDocument = $travelRequest->g_handover_document;

        $this->returnToRequester($travelRequest, $supervisor);

        $this->actingAs($staff)
            ->patch(route('travel-requests.update', $travelRequest), $this->payload($staff, [
                'g_handover_document' => UploadedFile::fake()->create('replacement.pdf', 90, 'application/pdf'),
            ]))
            ->assertRedirect();

        $travelRequest->refresh();

        $this->assertNotSame($originalDocument, $travelRequest->g_handover_document);
        Storage::disk('private')->assertExists($travelRequest->g_handover_document);
        Storage::disk('private')->assertMissing($originalDocument);
    }

    public function test_a_stale_tab_cannot_overwrite_a_request_that_moved_on(): void
    {
        [$staff, $supervisor, $centre] = $this->centreWithStaff();

        $travelRequest = TravelRequest::factory()->create([
            'requester_id' => $staff->id,
            'unit_id' => $centre->id,
            'status' => TravelRequest::STATUS_DRAFT,
            'b_destination' => 'Original Destination',
        ]);

        // The row is approved elsewhere between page render and form submit.
        TravelRequest::whereKey($travelRequest->getKey())->update([
            'status' => TravelRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($staff)
            ->patch(route('travel-requests.update', $travelRequest), $this->payload($staff, [
                'b_destination' => 'Overwritten Destination',
            ]))
            ->assertForbidden();

        $travelRequest->refresh();

        $this->assertSame(TravelRequest::STATUS_APPROVED, $travelRequest->status);
        $this->assertSame('Original Destination', $travelRequest->b_destination);
    }

    public function test_cancelling_a_request_that_was_already_approved_is_refused(): void
    {
        [$staff, , $centre] = $this->centreWithStaff();

        $travelRequest = TravelRequest::factory()->pending()->create([
            'requester_id' => $staff->id,
            'unit_id' => $centre->id,
        ]);

        TravelRequest::whereKey($travelRequest->getKey())->update([
            'status' => TravelRequest::STATUS_APPROVED,
        ]);

        $this->actingAs($staff)
            ->delete(route('travel-requests.cancel', $travelRequest))
            ->assertForbidden();

        $this->assertSame(TravelRequest::STATUS_APPROVED, $travelRequest->fresh()->status);
    }

    public function test_resubmitting_after_a_transfer_moves_the_request_to_the_new_unit(): void
    {
        [$staff, $supervisor] = $this->centreWithStaff();

        $travelRequest = $this->submit($staff);
        $this->returnToRequester($travelRequest, $supervisor);

        // The chain is rebuilt from scratch once the old chain is cleared.
        $newCentre = Unit::factory()->researchCentre()->create(['name' => 'Receiving Centre']);
        $newManager = User::factory()->centreManager()->create(['unit_id' => $newCentre->id]);
        $newSupervisor = User::factory()->supervisor()->create(['unit_id' => $newCentre->id]);

        $staff->forceFill([
            'unit_id' => $newCentre->id,
            'supervisor_id' => $newSupervisor->id,
        ])->save();

        $this->actingAs($staff->fresh())
            ->patch(route('travel-requests.update', $travelRequest), $this->payload($staff))
            ->assertRedirect();

        $travelRequest->refresh();

        $this->assertSame($newCentre->id, $travelRequest->unit_id);
        $this->assertSame($newSupervisor->id, $travelRequest->current_approver_id);
        $this->assertSame(
            $newManager->id,
            (int) collect($travelRequest->approval_chain)->last()['approver_id'],
        );
    }

    public function test_permit_numbers_advance_past_cancelled_requests(): void
    {
        [$staff] = $this->centreWithStaff();

        $first = $this->submit($staff);
        $this->assertSame('NIMR-ITP-'.now()->year.'-001', $first->request_number);

        // A row-counting generator would hand 001 out a second time here.
        $this->actingAs($staff)
            ->delete(route('travel-requests.cancel', $first))
            ->assertRedirect();

        $second = $this->submit($staff);

        $this->assertSame('NIMR-ITP-'.now()->year.'-002', $second->request_number);
    }

    public function test_the_database_rejects_a_duplicate_permit_number(): void
    {
        [$staff, , $centre] = $this->centreWithStaff();

        $existing = TravelRequest::factory()->create([
            'requester_id' => $staff->id,
            'unit_id' => $centre->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        TravelRequest::factory()->create([
            'requester_id' => $staff->id,
            'unit_id' => $centre->id,
            'request_number' => $existing->request_number,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    /**
     * @return array{0: User, 1: User, 2: Unit}
     */
    private function centreWithStaff(): array
    {
        $centre = Unit::factory()->researchCentre()->create();
        User::factory()->centreManager()->create(['unit_id' => $centre->id]);
        $supervisor = User::factory()->supervisor()->create(['unit_id' => $centre->id]);
        $staff = User::factory()->staff()->create([
            'unit_id' => $centre->id,
            'supervisor_id' => $supervisor->id,
            'job_title' => 'Research Officer',
        ]);

        return [$staff, $supervisor, $centre];
    }

    private function submit(User $staff): TravelRequest
    {
        $this->actingAs($staff)
            ->post(route('travel-requests.store'), $this->payload($staff))
            ->assertRedirect();

        return TravelRequest::where('requester_id', $staff->id)->latest('id')->firstOrFail();
    }

    private function returnToRequester(TravelRequest $travelRequest, User $approver): void
    {
        $this->actingAs($approver)
            ->post(route('travel-requests.approve', $travelRequest), [
                'decision' => 'returned',
                'comment' => 'Please attach the invitation letter.',
            ])
            ->assertRedirect();

        $travelRequest->refresh();

        $this->assertSame(TravelRequest::STATUS_RETURNED, $travelRequest->status);
    }

    private function payload(User $traveller, array $extra = []): array
    {
        return array_merge([
            'action' => 'submit',
            'b_phone' => '+255752000000',
            'b_destination' => 'Dar es Salaam',
            'b_departure_date' => now()->addDays(5)->toDateString(),
            'b_return_date' => now()->addDays(8)->toDateString(),
            'd_benefit_to_institution' => 'The trip supports institutional objectives.',
            'd_benefit_to_nation' => 'The trip supports public health delivery.',
            'd_consequences_if_rejected' => 'Important coordination work will be delayed.',
            'e_transport_costs' => '100000',
            'f_previous_travel_impact' => 'Previous travel improved collaboration.',
            'g_handover_officer_name' => 'Handover Officer',
            'g_handover_officer_title' => 'Administrator',
            'g_handover_document' => UploadedFile::fake()->create('handover.pdf', 120, 'application/pdf'),
        ], $extra);
    }
}
