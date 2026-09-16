<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_resending_to_a_rejected_address_shows_an_error_instead_of_failing(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'typo@nimr.or.tz']);

        $this->mock(ChannelManager::class, function ($mock) {
            $mock->shouldReceive('send')->andThrow(new UnexpectedResponseException(
                'Expected response code "250/251/252" but got code "550", with message "550 5.1.1 <typo@nimr.or.tz>: Recipient address rejected: User unknown in virtual mailbox table".',
                550,
            ));
        });

        $response = $this->actingAs($user)->from('/verify-email')->post('/email/verification-notification');

        $response->assertRedirect('/verify-email');
        $response->assertSessionHasErrors('email');
        $this->followRedirects($response)->assertSee(__('auth.email_undeliverable', ['email' => 'typo@nimr.or.tz']));
    }

    public function test_resending_while_the_mail_server_is_down_still_reports_the_fault(): void
    {
        $user = User::factory()->unverified()->create();

        $this->mock(ChannelManager::class, function ($mock) {
            $mock->shouldReceive('send')->andThrow(new TransportException('Connection could not be established with host "mail.nimr.or.tz:587"'));
        });
        Exceptions::fake();

        $response = $this->actingAs($user)->from('/verify-email')->post('/email/verification-notification');

        $response->assertRedirect('/verify-email');
        $response->assertSessionHasErrors(['email' => __('auth.email_send_failed')]);
        Exceptions::assertReported(TransportException::class);
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
