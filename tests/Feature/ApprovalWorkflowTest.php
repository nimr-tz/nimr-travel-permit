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

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    // ─── Shared org fixtures ────────────────────────────────────────────

    private User $dg;
    private User $hqHr;
    private Unit $hrmasUnit;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('private');

        // HR receives copies only; it is not an approval step.
        $this->dg = User::factory()->directorGeneral()->create(['unit_id' => null]);

        $this->hrmasUnit = Unit::factory()->hqStandalone()->create(['code' => 'HRMAS']);
        $this->hqHr      = User::factory()->hr()->create(['unit_id' => $this->hrmasUnit->id]);
    }

    // ─── Helper ─────────────────────────────────────────────────────────

    private function submitRequest(User $traveller, array $extra = []): TravelRequest
    {
        $response = $this->actingAs($traveller)->post(route('travel-requests.store'), $this->validRequestPayload($traveller, $extra));

        $response->assertRedirect();
        $this->assertDatabaseHas('travel_requests', ['requester_id' => $traveller->id]);

        return TravelRequest::where('requester_id', $traveller->id)->latest()->first();
    }

    private function validRequestPayload(User $traveller, array $extra = []): array
    {
        return array_merge([
            'action'           => 'submit',
            'b_applicant_name' => $traveller->name,
            'b_phone'          => '+255752000000',
            'b_email'          => $traveller->email,
            'b_position'       => $traveller->job_title ?? 'Research Officer',
            'b_destination'    => 'Dar es Salaam',
            'b_departure_date' => now()->addDays(5)->toDateString(),
            'b_return_date'    => now()->addDays(8)->toDateString(),
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

    private function approve(User $approver, TravelRequest $tr, string $decision = 'approved', ?string $comment = null): void
    {
        $this->actingAs($approver)
            ->post(route('travel-requests.approve', $tr), [
                'decision' => $decision,
                'comment'  => $comment,
            ])
            ->assertRedirect(route('travel-requests.show', $tr));
    }

    // ─── Research Centre ────────────────────────────────────────────────

    public function test_centre_staff_with_supervisor_chain(): void
    {
        $centreUnit    = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centreUnit->id]);
        $centreHr      = User::factory()->hr()->create(['unit_id' => $centreUnit->id]);
        $supervisor    = User::factory()->supervisor()->create(['unit_id' => $centreUnit->id]);
        $staff         = User::factory()->staff()->create(['unit_id' => $centreUnit->id, 'supervisor_id' => $supervisor->id]);

        $tr = $this->submitRequest($staff);

        $this->assertEquals(TravelRequest::STATUS_PENDING, $tr->status);
        $this->assertEquals($supervisor->id, $tr->current_approver_id);

        // Supervisor approves
        $this->approve($supervisor, $tr);
        $tr->refresh();
        $this->assertEquals($centreManager->id, $tr->current_approver_id);

        // Centre Manager approves
        $this->approve($centreManager, $tr);
        $tr->refresh();
        $this->assertEquals(TravelRequest::STATUS_APPROVED, $tr->status);
        $this->assertNull($tr->current_approver_id);
    }

    public function test_centre_staff_without_supervisor_cannot_submit(): void
    {
        $centreUnit    = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centreUnit->id]);
        $centreHr      = User::factory()->hr()->create(['unit_id' => $centreUnit->id]);
        $staff         = User::factory()->staff()->create(['unit_id' => $centreUnit->id, 'supervisor_id' => null]);

        $this->actingAs($staff)
            ->post(route('travel-requests.store'), $this->validRequestPayload($staff))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('dashboard.supervisor_required_to_submit'));

        $this->assertDatabaseMissing('travel_requests', ['requester_id' => $staff->id]);
    }

    public function test_staff_placed_directly_in_a_directorate_cannot_submit(): void
    {
        // Only the Director belongs directly in a Directorate unit. If a staff
        // member ends up there (e.g. legacy data, admin mistake), they must be
        // blocked rather than silently skipping straight to the DG.
        $directorate = Unit::factory()->hqDirectorate()->create();
        $staff       = User::factory()->staff()->create(['unit_id' => $directorate->id]);

        $this->actingAs($staff)
            ->post(route('travel-requests.store'), $this->validRequestPayload($staff))
            ->assertSessionHasErrors('submit');

        $this->assertDatabaseMissing('travel_requests', ['requester_id' => $staff->id]);
    }

    public function test_wrong_approver_gets_403(): void
    {
        $centreUnit    = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centreUnit->id]);
        $centreHr      = User::factory()->hr()->create(['unit_id' => $centreUnit->id]);
        $supervisor    = User::factory()->supervisor()->create(['unit_id' => $centreUnit->id]);
        $staff         = User::factory()->staff()->create(['unit_id' => $centreUnit->id, 'supervisor_id' => $supervisor->id]);
        $otherUser     = User::factory()->supervisor()->create(['unit_id' => $centreUnit->id]);

        $tr = $this->submitRequest($staff);

        // otherUser is not the current approver
        $this->actingAs($otherUser)
            ->post(route('travel-requests.approve', $tr), ['decision' => 'approved'])
            ->assertStatus(403);

        // Centre Manager is not the first step
        $this->actingAs($centreManager)
            ->post(route('travel-requests.approve', $tr), ['decision' => 'approved'])
            ->assertStatus(403);
    }

    public function test_rejected_request_is_terminal(): void
    {
        $centreUnit    = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centreUnit->id]);
        User::factory()->hr()->create(['unit_id' => $centreUnit->id]);
        $supervisor = User::factory()->supervisor()->create(['unit_id' => $centreUnit->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $centreUnit->id, 'supervisor_id' => $supervisor->id]);

        $tr = $this->submitRequest($staff);

        $this->approve($supervisor, $tr, 'rejected', 'Not approved for budget reasons.');
        $tr->refresh();

        $this->assertEquals(TravelRequest::STATUS_REJECTED, $tr->status);
        $this->assertNull($tr->current_approver_id);
    }

    public function test_returned_request_can_be_resubmitted(): void
    {
        $centreUnit    = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centreUnit->id]);
        $centreHr      = User::factory()->hr()->create(['unit_id' => $centreUnit->id]);
        $supervisor    = User::factory()->supervisor()->create(['unit_id' => $centreUnit->id]);
        $staff         = User::factory()->staff()->create(['unit_id' => $centreUnit->id, 'supervisor_id' => $supervisor->id]);

        $tr = $this->submitRequest($staff);

        $this->approve($supervisor, $tr, 'returned', 'Please attach the invitation letter.');
        $tr->refresh();

        $this->assertEquals(TravelRequest::STATUS_RETURNED, $tr->status);
        $this->assertNull($tr->current_approver_id);
        $this->assertNull($tr->approval_chain);

        // Requester edits and resubmits
        $this->actingAs($staff)
            ->patch(route('travel-requests.update', $tr), $this->validRequestPayload($staff, [
                'action'           => 'submit',
                'b_applicant_name' => $staff->name,
            ]))
            ->assertRedirect();

        $tr->refresh();
        $this->assertEquals(TravelRequest::STATUS_PENDING, $tr->status);
        $this->assertEquals($supervisor->id, $tr->current_approver_id);

        // Chain completes normally
        $this->approve($supervisor, $tr);
        $this->approve($centreManager, $tr);
        $this->assertEquals(TravelRequest::STATUS_APPROVED, $tr->fresh()->status);
    }

    // ─── Visibility ─────────────────────────────────────────────────────

    public function test_staff_cannot_see_another_staffs_request(): void
    {
        $centreUnit = Unit::factory()->researchCentre()->create();
        User::factory()->centreManager()->create(['unit_id' => $centreUnit->id]);
        User::factory()->hr()->create(['unit_id' => $centreUnit->id]);

        $staffA = User::factory()->staff()->create(['unit_id' => $centreUnit->id]);
        $staffB = User::factory()->staff()->create(['unit_id' => $centreUnit->id]);

        $tr = TravelRequest::factory()->create([
            'requester_id'     => $staffA->id,
            'unit_id'          => $centreUnit->id,
            'status'           => TravelRequest::STATUS_DRAFT,
            'b_applicant_name' => $staffA->name,
        ]);

        $this->actingAs($staffB)
            ->get(route('travel-requests.show', $tr))
            ->assertStatus(403);
    }

    public function test_dg_can_see_any_request(): void
    {
        $centreUnit = Unit::factory()->researchCentre()->create();
        $staff      = User::factory()->staff()->create(['unit_id' => $centreUnit->id]);

        $tr = TravelRequest::factory()->create([
            'requester_id'     => $staff->id,
            'unit_id'          => $centreUnit->id,
            'status'           => TravelRequest::STATUS_DRAFT,
            'b_applicant_name' => $staff->name,
        ]);

        $this->actingAs($this->dg)
            ->get(route('travel-requests.show', $tr))
            ->assertOk();
    }

    public function test_centre_hr_can_only_see_own_centre_requests(): void
    {
        $centreA = Unit::factory()->researchCentre()->create();
        $centreB = Unit::factory()->researchCentre()->create();

        $hrA   = User::factory()->hr()->create(['unit_id' => $centreA->id]);
        $staffB = User::factory()->staff()->create(['unit_id' => $centreB->id]);

        $tr = TravelRequest::factory()->create([
            'requester_id'     => $staffB->id,
            'unit_id'          => $centreB->id,
            'status'           => TravelRequest::STATUS_DRAFT,
            'b_applicant_name' => $staffB->name,
        ]);

        $this->actingAs($hrA)
            ->get(route('travel-requests.show', $tr))
            ->assertStatus(403);
    }

    // ─── HQ Section chain ────────────────────────────────────────────────

    public function test_hq_section_staff_chain(): void
    {
        $directorate = Unit::factory()->hqDirectorate()->create();
        $section     = Unit::factory()->hqSection()->create(['parent_id' => $directorate->id]);

        $sectionHead = User::factory()->head()->create(['unit_id' => $section->id]);
        $director    = User::factory()->director()->create(['unit_id' => $directorate->id]);
        $staff       = User::factory()->staff()->create(['unit_id' => $section->id, 'supervisor_id' => $sectionHead->id]);

        $tr = $this->submitRequest($staff);

        $this->assertEquals($sectionHead->id, $tr->current_approver_id);

        $this->approve($sectionHead, $tr);
        $tr->refresh();
        $this->assertEquals($director->id, $tr->current_approver_id);

        $this->approve($director, $tr);
        $tr->refresh();
        $this->assertEquals($this->dg->id, $tr->current_approver_id);

        $this->approve($this->dg, $tr);
        $tr->refresh();
        $this->assertEquals(TravelRequest::STATUS_APPROVED, $tr->status);
        $this->assertNull($tr->current_approver_id);
    }
}
