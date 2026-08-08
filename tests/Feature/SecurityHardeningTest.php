<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression cover for the authorization and session issues found in the
 * pre-release security audit.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_endpoints_no_longer_exist(): void
    {
        $this->get('/debug/data?token=nimr-debug-2026')->assertNotFound();
        $this->get('/debug/cleanup?token=nimr-debug-2026&confirm=yes')->assertNotFound();

        // The destructive endpoint must not have taken anyone with it.
        $this->assertDatabaseCount('users', 0);
    }

    public function test_centre_hr_officer_only_sees_their_own_centre(): void
    {
        [$ownCentre] = $this->requestInCentre('Amani Research Centre', 'Amani Traveller');
        $this->requestInCentre('Mbeya Research Centre', 'Mbeya Traveller');

        $centreHr = User::factory()->hr()->create(['unit_id' => $ownCentre->id]);

        $this->actingAs($centreHr)
            ->get(route('hr.reports.index'))
            ->assertOk()
            ->assertSee('Amani Traveller')
            ->assertDontSee('Mbeya Traveller')
            ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 1)
            ->assertViewHas('units', fn ($units) => $units->count() === 1);
    }

    public function test_centre_hr_export_cannot_reach_other_centres(): void
    {
        [$ownCentre, $ownRequest] = $this->requestInCentre('Amani Research Centre', 'Amani Traveller');
        [$otherCentre, $otherRequest] = $this->requestInCentre('Mbeya Research Centre', 'Mbeya Traveller');

        $centreHr = User::factory()->hr()->create(['unit_id' => $ownCentre->id]);

        // Even when explicitly asking for the other centre by id.
        $body = $this->actingAs($centreHr)
            ->get(route('hr.reports.export', ['unit_id' => $otherCentre->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($otherRequest->request_number, $body);
        $this->assertStringNotContainsString('Mbeya Traveller', $body);

        // And the officer's own centre is still exportable.
        $ownBody = $this->actingAs($centreHr)
            ->get(route('hr.reports.export'))
            ->getContent();

        $this->assertStringContainsString($ownRequest->request_number, $ownBody);
    }

    public function test_hq_hr_officer_still_sees_every_centre(): void
    {
        $this->requestInCentre('Amani Research Centre', 'Amani Traveller');
        $this->requestInCentre('Mbeya Research Centre', 'Mbeya Traveller');

        $hqUnit = Unit::factory()->hqSection()->create(['name' => 'Human Resources']);
        $hqHr = User::factory()->hr()->create(['unit_id' => $hqUnit->id]);

        $this->actingAs($hqHr)
            ->get(route('hr.reports.index'))
            ->assertOk()
            ->assertSee('Amani Traveller')
            ->assertSee('Mbeya Traveller')
            ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 2);
    }

    public function test_csv_export_neutralises_spreadsheet_formulas(): void
    {
        $centre = Unit::factory()->researchCentre()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $centre->id]);

        TravelRequest::factory()->pending()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $centre->id,
            'b_destination' => '=HYPERLINK("http://evil.example","click")',
        ]);

        $dg = User::factory()->directorGeneral()->create();

        $body = $this->actingAs($dg)->get(route('hr.reports.export'))->getContent();

        $this->assertStringNotContainsString(',=HYPERLINK', $body);
        $this->assertStringContainsString("'=HYPERLINK", $body);
    }

    public function test_deactivated_user_is_logged_out_on_their_next_request(): void
    {
        $user = User::factory()->staff()->create(['unit_id' => Unit::factory()->create()->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_deactivating_a_user_revokes_their_stored_sessions(): void
    {
        // The test environment runs the array session driver; session rows only
        // exist to be revoked when the database driver is in use.
        config(['session.driver' => 'database']);

        $unit = Unit::factory()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $target = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => null,
        ]);

        DB::table('sessions')->insert([
            'id' => 'session-under-test',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $rememberToken = $target->remember_token;

        $this->actingAs($admin)->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'staff',
            'unit_id' => $unit->id,
            'is_active' => '0',
        ]);

        $target->refresh();

        $this->assertFalse((bool) $target->is_active);
        $this->assertNotSame($rememberToken, $target->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'session-under-test']);
    }

    public function test_account_closure_is_blocked_while_approvals_are_waiting(): void
    {
        $unit = Unit::factory()->create();
        $approver = User::factory()->manager()->create(['unit_id' => $unit->id]);
        $requester = User::factory()->staff()->create(['unit_id' => $unit->id]);

        TravelRequest::factory()->pending()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'current_approver_id' => $approver->id,
        ]);

        $this->actingAs($approver)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrorsIn('userDeletion', 'closure');

        $approver->refresh();
        $this->assertTrue((bool) $approver->is_active);
    }

    /**
     * @return array{0: Unit, 1: TravelRequest}
     */
    private function requestInCentre(string $centreName, string $travellerName): array
    {
        $centre = Unit::factory()->researchCentre()->create(['name' => $centreName]);
        $traveller = User::factory()->staff()->create([
            'name' => $travellerName,
            'unit_id' => $centre->id,
        ]);

        $request = TravelRequest::factory()->pending()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $centre->id,
            'b_applicant_name' => $travellerName,
        ]);

        return [$centre, $request];
    }
}
