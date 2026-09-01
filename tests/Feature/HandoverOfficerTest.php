<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\TravelRequestHandoverNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HandoverOfficerTest extends TestCase
{
    use RefreshDatabase;

    private Unit $centre;
    private User $traveller;
    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'director_general', 'unit_id' => null]);

        $this->centre = Unit::factory()->create(['type' => 'research_centre']);
        $cm = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'centre_manager']);
        $supervisor = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $cm->id]);
        $this->traveller = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'staff', 'supervisor_id' => $supervisor->id]);
        $this->colleague = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'staff', 'supervisor_id' => $supervisor->id]);
    }

    public function test_the_picker_only_lists_active_colleagues_in_the_same_unit(): void
    {
        $otherCentre = Unit::factory()->create(['type' => 'research_centre']);
        $outsider = User::factory()->create(['unit_id' => $otherCentre->id, 'role' => 'staff']);
        $inactive = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'staff', 'is_active' => false]);

        $offered = $this->actingAs($this->traveller)
            ->get('/travel-requests/create')
            ->assertOk()
            ->viewData('handoverUsers')
            ->pluck('id')
            ->all();

        $this->assertContains($this->colleague->id, $offered, 'a colleague in the same unit was missing');
        $this->assertNotContains($outsider->id, $offered, 'a user from another unit was offered');
        $this->assertNotContains($inactive->id, $offered, 'a deactivated user was offered');
        $this->assertNotContains($this->traveller->id, $offered, 'the traveller was offered to themselves');
    }

    public function test_the_officer_name_is_derived_from_the_id_not_the_request_body(): void
    {
        $this->actingAs($this->traveller)->post('/travel-requests', $this->payload([
            'g_handover_officer_id' => $this->colleague->id,
            'g_handover_officer_name' => 'Somebody Entirely Invented',
            'g_handover_officer_title' => 'Supreme Commander',
        ]))->assertSessionHasNoErrors();

        $tr = \App\Models\TravelRequest::latest('id')->firstOrFail();

        $this->assertSame($this->colleague->id, $tr->g_handover_officer_id);
        $this->assertSame($this->colleague->name, $tr->g_handover_officer_name, 'a forged name was trusted from the form');
        $this->assertNotSame('Supreme Commander', $tr->g_handover_officer_title);
    }

    public function test_an_officer_outside_the_unit_is_not_bound(): void
    {
        $outsider = User::factory()->create(['unit_id' => Unit::factory()->create(['type' => 'research_centre'])->id]);

        $this->actingAs($this->traveller)->post('/travel-requests', $this->payload([
            'g_handover_officer_id' => $outsider->id,
            'g_handover_officer_name' => $outsider->name,
        ]))->assertSessionHasNoErrors();

        $tr = \App\Models\TravelRequest::latest('id')->firstOrFail();
        $this->assertNull($tr->g_handover_officer_id, 'an out-of-unit officer was accepted');
    }

    public function test_the_officer_is_notified_when_the_permit_is_submitted(): void
    {
        Notification::fake();

        $this->actingAs($this->traveller)->post('/travel-requests', $this->payload([
            'g_handover_officer_id' => $this->colleague->id,
        ]))->assertSessionHasNoErrors();

        Notification::assertSentTo($this->colleague, TravelRequestHandoverNotification::class,
            fn ($n) => $n->stage === TravelRequestHandoverNotification::STAGE_SUBMITTED);
    }

    public function test_no_notification_is_sent_when_no_officer_is_bound(): void
    {
        Notification::fake();

        $this->actingAs($this->traveller)->post('/travel-requests', $this->payload())
            ->assertSessionHasNoErrors();

        Notification::assertNothingSentTo($this->colleague);
    }

    public function test_a_draft_does_not_notify_the_officer(): void
    {
        Notification::fake();

        $this->actingAs($this->traveller)->post('/travel-requests', $this->payload([
            'action' => 'draft',
            'g_handover_officer_id' => $this->colleague->id,
        ]))->assertSessionHasNoErrors();

        Notification::assertNothingSentTo($this->colleague);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'action' => 'submit',
            'b_phone' => '+255 700 000 001',
            'b_destination' => 'Arusha',
            'b_departure_date' => now()->addWeek()->toDateString(),
            'b_return_date' => now()->addWeeks(2)->toDateString(),
            'd_benefit_to_institution' => 'Shares findings across the institute.',
            'd_benefit_to_nation' => 'Informs national malaria policy.',
            'd_consequences_if_rejected' => 'NIMR loses its presentation slot.',
            'e_govt_cost_i' => '1,200,000',
            'f_previous_travel_impact' => 'Improved lab throughput.',
            'g_handover_officer_name' => $this->colleague->name,
            'g_handover_document' => UploadedFile::fake()->create('handover.pdf', 40, 'application/pdf'),
        ], $overrides);
    }
}
