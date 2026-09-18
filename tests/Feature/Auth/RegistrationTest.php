<?php

namespace Tests\Feature\Auth;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $unit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@nimr.or.tz',
            'organizational_level' => 'research_centre',
            'unit_id' => $unit->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@nimr.or.tz',
            'unit_id' => $unit->id,
        ]);
    }

    public function test_registration_sends_exactly_one_verification_email(): void
    {
        Notification::fake();

        $unit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@nimr.or.tz',
            'organizational_level' => 'research_centre',
            'unit_id' => $unit->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Notification::assertSentToTimes(User::firstWhere('email', 'test@nimr.or.tz'), VerifyEmail::class, 1);
    }

    public function test_duplicate_registration_returns_validation_error(): void
    {
        $unit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        User::factory()->create([
            'email' => 'test@nimr.or.tz',
            'unit_id' => $unit->id,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'TEST@NIMR.OR.TZ',
            'organizational_level' => 'research_centre',
            'unit_id' => $unit->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'test@nimr.or.tz')->count());
    }

    public function test_registration_with_an_address_the_mail_server_rejects_leaves_no_account(): void
    {
        // Production 2026-09-14: the NIMR mail server answered RCPT TO with
        // "550 5.1.1 User unknown in virtual mailbox table". That used to be a
        // 500 page, and the account was already saved, so the person could not
        // register again with a corrected address.
        $this->mock(ChannelManager::class, function ($mock) {
            $mock->shouldReceive('send')->andThrow(new UnexpectedResponseException(
                'Expected response code "250/251/252" but got code "550", with message "550 5.1.1 <typo@nimr.or.tz>: Recipient address rejected: User unknown in virtual mailbox table".',
                550,
            ));
        });

        $unit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'typo@nimr.or.tz',
            'organizational_level' => 'research_centre',
            'unit_id' => $unit->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors(['email' => __('auth.email_undeliverable', ['email' => 'typo@nimr.or.tz'])]);
        $this->assertDatabaseMissing('users', ['email' => 'typo@nimr.or.tz']);
    }

    public function test_registration_rejects_mismatched_organizational_level_and_unit(): void
    {
        $unit = Unit::create([
            'name' => 'ICT Unit',
            'code' => 'ICT',
            'type' => 'hq_standalone',
            'is_active' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@nimr.or.tz',
            'organizational_level' => 'research_centre',
            'unit_id' => $unit->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('unit_id');
        $this->assertDatabaseMissing('users', [
            'email' => 'test@nimr.or.tz',
        ]);
    }

    public function test_registration_rejects_placement_directly_in_a_directorate(): void
    {
        // Only the Director belongs directly in a Directorate unit — that account
        // is created by a system administrator. A self-registered "staff" placed
        // there would otherwise skip the entire approval chain and go straight to
        // the DG, bypassing supervisor/section-head/director review.
        $directorate = Unit::create([
            'name' => 'Research Coordination and Promotion Directorate',
            'code' => 'RCPD',
            'type' => 'hq_directorate',
            'is_active' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@nimr.or.tz',
            'organizational_level' => 'headquarters',
            'unit_id' => $directorate->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('unit_id');
        $this->assertDatabaseMissing('users', [
            'email' => 'test@nimr.or.tz',
        ]);
    }
}
