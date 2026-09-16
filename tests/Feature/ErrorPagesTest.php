<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Production runs with debug off; with it on Laravel shows the stack trace instead.
        config(['app.debug' => false]);

        Route::middleware('web')->group(function () {
            Route::get('/_test/abort/{code}', fn (int $code) => abort($code));
            Route::get('/_test/forbidden-with-reason', fn () => abort(403, 'Your account has been deactivated.'));
            Route::get('/_test/crash', fn () => throw new \RuntimeException('secret internal detail'));
            Route::get('/_test/throttled', fn () => 'ok')->middleware('throttle:1,1');
        });
    }

    public function test_each_status_gets_a_branded_page_in_the_users_language(): void
    {
        foreach ([401, 403, 404, 413, 419, 429, 500, 503] as $code) {
            foreach (['en', 'sw'] as $locale) {
                $this->withSession(['locale' => $locale])
                    ->get("/_test/abort/{$code}")
                    ->assertStatus($code)
                    ->assertSee(trans("errors.{$code}.title", locale: $locale))
                    ->assertSee(trans('errors.code', ['code' => $code], $locale))
                    ->assertSee(trans('errors.go_back', locale: $locale));
            }
        }
    }

    public function test_unlisted_codes_use_the_generic_pages(): void
    {
        $this->withSession(['locale' => 'en'])->get('/_test/abort/405')
            ->assertStatus(405)
            ->assertSee(__('errors.4xx.title'))
            ->assertSee('Error 405');

        $this->withSession(['locale' => 'en'])->get('/_test/abort/502')
            ->assertStatus(502)
            ->assertSee(__('errors.500.title'))
            ->assertSee('Error 502');
    }

    public function test_unknown_url_is_a_404_in_the_users_language(): void
    {
        $this->withSession(['locale' => 'sw'])->get('/hakuna-ukurasa-huu')
            ->assertNotFound()
            ->assertSee('Ukurasa haukupatikana');
    }

    public function test_a_crash_never_shows_internal_details(): void
    {
        Exceptions::fake();

        $this->withSession(['locale' => 'en'])->get('/_test/crash')
            ->assertStatus(500)
            ->assertSee(__('errors.500.title'))
            ->assertDontSee('secret internal detail')
            ->assertDontSee('RuntimeException');
    }

    public function test_forbidden_page_shows_a_deliberate_reason_only(): void
    {
        $this->withSession(['locale' => 'en'])->get('/_test/forbidden-with-reason')
            ->assertForbidden()
            ->assertSee('Your account has been deactivated.');
    }

    public function test_throttled_page_says_how_long_to_wait(): void
    {
        $this->withSession(['locale' => 'en'])->get('/_test/throttled')->assertOk();

        $this->withSession(['locale' => 'en'])->get('/_test/throttled')
            ->assertStatus(429)
            ->assertSee(__('errors.429.title'))
            ->assertSee('You can try again in');
    }

    public function test_error_page_renders_for_a_signed_in_user(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $this->actingAs($user)->withSession(['locale' => 'en'])->get('/_test/abort/403')
            ->assertForbidden()
            ->assertSee(__('errors.403.title'));
    }
}
