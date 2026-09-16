<?php

namespace App\Providers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditLogger::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->auditAuthenticationEvents();
    }

    /**
     * Sign-in activity comes from the framework's own auth events, so it is
     * captured however the sign-in happened (form, remember-me cookie, reset).
     */
    private function auditAuthenticationEvents(): void
    {
        Event::listen(function (Login $event) {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.login', $event->user,
                    context: ['remember_me' => $event->remember ?: null], actor: $event->user);
            }
        });

        Event::listen(function (Logout $event) {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.logout', $event->user, actor: $event->user);
            }
        });

        Event::listen(function (Failed $event) {
            $email = $event->credentials['email'] ?? null;
            $account = $event->user instanceof User
                ? $event->user
                : ($email ? User::where('email', $email)->first() : null);

            app(AuditLogger::class)->log('auth.login_failed', $account,
                context: ['email' => $email, 'known_account' => $account ? null : false]);
        });

        Event::listen(function (Lockout $event) {
            $email = $event->request->input('email');

            app(AuditLogger::class)->log('auth.lockout',
                $email ? User::where('email', $email)->first() : null,
                context: ['email' => $email]);
        });

        Event::listen(function (PasswordReset $event) {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.password_reset', $event->user, actor: $event->user);
            }
        });

        Event::listen(function (Verified $event) {
            if ($event->user instanceof User) {
                app(AuditLogger::class)->log('auth.email_verified', $event->user, actor: $event->user);
            }
        });
    }
}
