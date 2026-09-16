<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Storage::fake('private');
    }

    // ─── Access ─────────────────────────────────────────────────────────

    public function test_only_system_admins_can_open_the_audit_log(): void
    {
        $unit = Unit::factory()->create();

        foreach (['staff', 'hr', 'director_general'] as $role) {
            $user = User::factory()->create(['role' => $role, 'unit_id' => $unit->id]);

            $this->actingAs($user)->get(route('audit-log.index'))->assertForbidden();
            $this->actingAs($user)->get(route('audit-log.export'))->assertForbidden();
        }

        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $this->actingAs($admin)->get(route('audit-log.index'))
            ->assertOk()
            ->assertSee(__('audit.title'));
    }

    public function test_hq_admin_sees_everything_and_centre_admin_only_their_centre(): void
    {
        $hq = Unit::factory()->hqStandalone()->create();
        $centre = Unit::factory()->researchCentre()->create();
        $otherCentre = Unit::factory()->researchCentre()->create();

        $hqAdmin = User::factory()->systemAdmin()->create(['unit_id' => $hq->id]);
        $centreAdmin = User::factory()->systemAdmin()->create(['unit_id' => $centre->id]);

        $inCentre = User::factory()->staff()->create(['unit_id' => $centre->id, 'name' => 'Centre Person']);
        $elsewhere = User::factory()->staff()->create(['unit_id' => $otherCentre->id, 'name' => 'Faraway Person']);

        app(AuditLogger::class)->log('auth.password_changed', $inCentre, actor: $inCentre);
        app(AuditLogger::class)->log('auth.password_changed', $elsewhere, actor: $elsewhere);

        $this->actingAs($hqAdmin)->get(route('audit-log.index'))
            ->assertSee('Centre Person')
            ->assertSee('Faraway Person');

        $this->actingAs($centreAdmin)->get(route('audit-log.index'))
            ->assertSee('Centre Person')
            ->assertDontSee('Faraway Person');

        // The unit filter cannot be used to widen a centre admin's view.
        $this->actingAs($centreAdmin)->get(route('audit-log.index', ['unit_id' => $otherCentre->id]))
            ->assertDontSee('Faraway Person');

        $csv = $this->actingAs($centreAdmin)->get(route('audit-log.export'))->streamedContent();
        $this->assertStringContainsString('Centre Person', $csv);
        $this->assertStringNotContainsString('Faraway Person', $csv);
    }

    // ─── Sign-in activity ───────────────────────────────────────────────

    public function test_successful_and_failed_sign_ins_are_recorded_without_the_password(): void
    {
        $user = User::factory()->staff()->create([
            'unit_id' => Unit::factory()->create()->id,
            'email'   => 'real@nimr.or.tz',
        ]);

        $this->post('/login', ['email' => 'real@nimr.or.tz', 'password' => 'WrongPass!234']);
        $this->post('/login', ['email' => 'nobody@nimr.or.tz', 'password' => 'Guess!234']);
        $this->post('/login', ['email' => 'real@nimr.or.tz', 'password' => 'password']);
        $this->post('/logout');

        $failed = ActivityLog::where('action', 'auth.login_failed')->orderBy('id')->get();
        $this->assertCount(2, $failed);
        $this->assertSame($user->id, $failed[0]->subject_id);
        $this->assertNull($failed[1]->subject_id);
        $this->assertSame('nobody@nimr.or.tz', $failed[1]->context['email']);

        $login = ActivityLog::where('action', 'auth.login')->sole();
        $this->assertSame($user->id, $login->actor_id);
        $this->assertSame($user->unit_id, $login->unit_id);

        $this->assertDatabaseHas('activity_logs', ['action' => 'auth.logout', 'actor_id' => $user->id]);

        foreach (ActivityLog::all() as $entry) {
            $stored = json_encode([$entry->changes, $entry->context]);
            $this->assertStringNotContainsString('WrongPass', $stored);
            $this->assertStringNotContainsString('Guess!234', $stored);
        }
    }

    // ─── Travel requests ────────────────────────────────────────────────

    public function test_submission_approval_and_downloads_are_recorded(): void
    {
        User::factory()->directorGeneral()->create(['unit_id' => null]);
        $centre = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centre->id]);
        $supervisor = User::factory()->supervisor()->create(['unit_id' => $centre->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $centre->id, 'supervisor_id' => $supervisor->id]);

        $this->actingAs($staff)->post(route('travel-requests.store'), [
            'action'           => 'submit',
            'b_applicant_name' => $staff->name,
            'b_email'          => $staff->email,
            'b_position'       => $staff->job_title ?? 'Research Officer',
            'b_phone'          => '+255752000000',
            'b_destination'    => 'Dar es Salaam',
            'b_departure_date' => now()->addDays(5)->toDateString(),
            'b_return_date'    => now()->addDays(8)->toDateString(),
            'd_benefit_to_institution'   => 'The trip supports institutional objectives.',
            'd_benefit_to_nation'        => 'The trip supports public health delivery.',
            'd_consequences_if_rejected' => 'Important coordination work will be delayed.',
            'e_transport_costs'          => '100000',
            'f_previous_travel_impact'   => 'Previous travel improved collaboration.',
            'g_handover_officer_name'    => 'Handover Officer',
            'g_handover_officer_title'   => 'Administrator',
            'g_handover_document'        => UploadedFile::fake()->create('handover.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $tr = TravelRequest::where('requester_id', $staff->id)->sole();

        $submitted = ActivityLog::where('action', 'travel_request.submitted')->sole();
        $this->assertSame($staff->id, $submitted->actor_id);
        $this->assertSame($tr->id, $submitted->subject_id);
        $this->assertSame($tr->request_number, $submitted->subject_label);
        $this->assertSame($supervisor->id, $submitted->context['first_approver_id']);

        $this->actingAs($supervisor)->post(route('travel-requests.approve', $tr), [
            'decision' => 'approved',
            'comment'  => 'Fine by me.',
        ]);

        $approved = ActivityLog::where('action', 'travel_request.approved')->sole();
        $this->assertSame($supervisor->id, $approved->actor_id);
        $this->assertSame('Fine by me.', $approved->context['comment']);
        $this->assertSame(TravelRequest::STATUS_PENDING, $approved->context['resulting_status']);
        $this->assertSame($centreManager->id, $approved->context['next_approver_id']);

        $this->actingAs($supervisor)->get(route('travel-requests.download', $tr))->assertOk();
        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'download.handover_document',
            'actor_id'   => $supervisor->id,
            'subject_id' => $tr->id,
        ]);
    }

    public function test_a_refused_action_is_not_recorded(): void
    {
        $centre = Unit::factory()->researchCentre()->create();
        $staff = User::factory()->staff()->create(['unit_id' => $centre->id]);
        $stranger = User::factory()->staff()->create(['unit_id' => $centre->id]);

        $tr = TravelRequest::factory()->pending()->create([
            'requester_id' => $staff->id,
            'unit_id'      => $centre->id,
        ]);

        $this->actingAs($stranger)->post(route('travel-requests.approve', $tr), ['decision' => 'approved'])
            ->assertForbidden();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'travel_request.approved']);
    }

    public function test_stale_request_auto_cancellation_is_recorded_as_a_system_action(): void
    {
        $centre = Unit::factory()->researchCentre()->create();
        $staff = User::factory()->staff()->create(['unit_id' => $centre->id]);

        $tr = TravelRequest::factory()->pending()->create([
            'requester_id'     => $staff->id,
            'unit_id'          => $centre->id,
            'b_departure_date' => now()->subDays(10)->toDateString(),
        ]);

        $this->artisan('travel-requests:cancel-stale', ['--before' => now()->toDateString(), '--apply' => true])
            ->assertSuccessful();

        $entry = ActivityLog::where('action', 'travel_request.auto_cancelled')->sole();
        $this->assertNull($entry->actor_id);
        $this->assertSame($tr->id, $entry->subject_id);
        $this->assertSame($centre->id, $entry->unit_id);
        $this->assertSame(TravelRequest::STATUS_CANCELLED, $entry->changes['after']['status']);
    }

    // ─── Administration ─────────────────────────────────────────────────

    public function test_deactivating_a_user_records_what_changed_but_never_the_password(): void
    {
        $unit = Unit::factory()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $target = User::factory()->staff()->create(['unit_id' => $unit->id, 'supervisor_id' => null]);

        $this->actingAs($admin)->patch(route('users.update', $target), [
            'name'      => $target->name,
            'email'     => $target->email,
            'password'  => 'BrandNewSecret99',
            'role'      => 'staff',
            'unit_id'   => $unit->id,
            'is_active' => '0',
        ])->assertRedirect(route('users.index'));

        $entry = ActivityLog::where('action', 'user.deactivated')->sole();
        $this->assertSame($admin->id, $entry->actor_id);
        $this->assertSame($target->id, $entry->subject_id);
        $this->assertTrue($entry->changes['before']['is_active']);
        $this->assertFalse($entry->changes['after']['is_active']);
        $this->assertArrayNotHasKey('name', $entry->changes['after']);
        $this->assertTrue($entry->context['password_set_by_admin']);
        $this->assertStringNotContainsString('BrandNewSecret99', json_encode($entry->toArray()));
    }

    // ─── The trail itself ───────────────────────────────────────────────

    public function test_entries_cannot_be_changed_or_deleted(): void
    {
        $user = User::factory()->staff()->create(['unit_id' => Unit::factory()->create()->id]);
        app(AuditLogger::class)->log('auth.password_changed', $user, actor: $user);
        $entry = ActivityLog::sole();

        try {
            $entry->update(['action' => 'auth.login']);
            $this->fail('An audit entry was updated.');
        } catch (LogicException) {
        }

        try {
            $entry->delete();
            $this->fail('An audit entry was deleted.');
        } catch (LogicException) {
        }

        $this->assertSame('auth.password_changed', $entry->fresh()->action);
    }

    public function test_page_filters_and_shows_readable_details(): void
    {
        $unit = Unit::factory()->hqStandalone()->create(['name' => 'ICT Unit']);
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $boss = User::factory()->manager()->create(['unit_id' => $unit->id, 'name' => 'Grace Boss']);
        $user = User::factory()->staff()->create(['unit_id' => $unit->id, 'name' => 'Juma Staff']);

        $audit = app(AuditLogger::class);
        $audit->log('account.supervisor_changed', $user,
            ['before' => ['supervisor_id' => null], 'after' => ['supervisor_id' => $boss->id]], actor: $user);
        $audit->log('auth.login', $admin, actor: $admin);

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('audit-log.index', ['category' => 'account']))
            ->assertOk()
            ->assertSee(__('audit.event.account.supervisor_changed', locale: 'en'))
            ->assertSee('Grace Boss')         // supervisor id shown as a name
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('action')->all() === ['account.supervisor_changed']);

        $this->actingAs($admin)->get(route('audit-log.index', ['event' => 'auth.login']))
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('actor_id')->all() === [$admin->id]);

        $this->actingAs($admin)->get(route('audit-log.index', ['q' => 'Juma']))
            ->assertViewHas('logs', fn ($logs) => $logs->pluck('actor_id')->all() === [$user->id]);

        $this->actingAs($admin)->get(route('audit-log.index', ['from' => now()->addDay()->toDateString()]))
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
    }

    public function test_export_downloads_csv_and_records_that_it_happened(): void
    {
        $unit = Unit::factory()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $user = User::factory()->staff()->create(['unit_id' => $unit->id, 'name' => '=cmd|calc']);
        app(AuditLogger::class)->log('auth.password_changed', $user, actor: $user);

        $response = $this->actingAs($admin)->get(route('audit-log.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Changed password', $csv);
        $this->assertStringContainsString("'=cmd|calc", $csv);

        $this->assertDatabaseHas('activity_logs', ['action' => 'download.audit_export', 'actor_id' => $admin->id]);
    }

    public function test_every_event_has_a_label_in_both_languages(): void
    {
        foreach (ActivityLog::allEvents() as $event) {
            foreach (['en', 'sw'] as $locale) {
                $this->assertNotSame("audit.event.{$event}", __("audit.event.{$event}", locale: $locale), "{$event} missing in {$locale}");
            }
        }

        foreach (array_keys(ActivityLog::EVENTS) as $category) {
            foreach (['en', 'sw'] as $locale) {
                $this->assertNotSame("audit.category.{$category}", __("audit.category.{$category}", locale: $locale));
            }
        }
    }
}
