<?php

namespace App\Services;

use App\Mail\CriticalErrorAlert;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class CriticalErrorAlertService
{
    private static bool $dispatchingAlert = false;

    public function handle(Throwable $exception, ?Request $request = null): void
    {
        if (!$this->shouldNotify($exception)) {
            return;
        }

        $recipients = config('mail.error_alerts.to', []);

        if ($recipients === []) {
            return;
        }

        $cacheKey = 'critical-error-alert:' . $this->signatureFor($exception, $request);
        $ttlMinutes = max(1, (int) config('mail.error_alerts.dedupe_minutes', 15));

        if (!Cache::add($cacheKey, now()->toIso8601String(), now()->addMinutes($ttlMinutes))) {
            return;
        }

        $context = $this->buildContext($exception, $request);

        try {
            self::$dispatchingAlert = true;

            Mail::mailer((string) config('mail.error_alerts.mailer', config('mail.default')))
                ->to($recipients)
                ->send(new CriticalErrorAlert($context));
        } catch (Throwable $mailException) {
            Log::error('Critical error alert dispatch failed', [
                'original_exception' => $exception::class,
                'original_message' => $exception->getMessage(),
                'mail_exception' => $mailException::class,
                'mail_message' => $mailException->getMessage(),
            ]);
        } finally {
            self::$dispatchingAlert = false;
        }
    }

    public function shouldNotify(Throwable $exception): bool
    {
        if (!config('mail.error_alerts.enabled')) {
            return false;
        }

        if (self::$dispatchingAlert) {
            return false;
        }

        if ($exception instanceof ValidationException
            || $exception instanceof AuthenticationException
            || $exception instanceof AuthorizationException
            || $exception instanceof ModelNotFoundException
            || $exception instanceof NotFoundHttpException
            || $exception instanceof TokenMismatchException) {
            return false;
        }

        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            return false;
        }

        return !str_contains($exception::class, 'CriticalErrorAlert');
    }

    public function buildContext(Throwable $exception, ?Request $request = null): array
    {
        $user = $request?->user();
        $route = $request?->route();

        return [
            'app_name' => config('app.name'),
            'app_env' => app()->environment(),
            'occurred_at' => now()->toDateTimeString(),
            'runtime' => [
                'running_in_console' => app()->runningInConsole(),
                'command' => app()->runningInConsole() ? implode(' ', $_SERVER['argv'] ?? []) : null,
                'host' => gethostname() ?: null,
                'php_sapi' => PHP_SAPI,
            ],
            'exception' => [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ],
            'request' => [
                'method' => $request?->method(),
                'url' => $request?->fullUrl(),
                'route_name' => $route?->getName(),
                'route_action' => $route?->getActionName(),
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'input' => $this->sanitizeInput($request),
                'headers' => $this->sanitizeHeaders($request),
            ],
            'user' => $user ? [
                'id' => $user->getAuthIdentifier(),
                'email' => $user->email ?? null,
                'name' => $user->name ?? null,
                'roles' => method_exists($user, 'roleNames') ? $user->roleNames() : [],
            ] : null,
            'trace' => collect($exception->getTrace())
                ->take(20)
                ->map(fn (array $frame) => Arr::only($frame, ['file', 'line', 'class', 'function']))
                ->values()
                ->all(),
            'previous' => $this->buildPreviousExceptionChain($exception),
        ];
    }

    private function signatureFor(Throwable $exception, ?Request $request = null): string
    {
        return sha1(implode('|', [
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            (string) $exception->getLine(),
            (string) $request?->path(),
            (string) $request?->method(),
        ]));
    }

    private function sanitizeInput(?Request $request): array
    {
        if (!$request) {
            return [];
        }

        return Arr::except($request->except([
            'password',
            'password_confirmation',
            'current_password',
        ]), [
            '_token',
            'token',
            'api_token',
            'authorization',
        ]);
    }

    private function sanitizeHeaders(?Request $request): array
    {
        if (!$request) {
            return [];
        }

        return collect($request->headers->all())
            ->except([
                'authorization',
                'cookie',
                'x-csrf-token',
                'x-xsrf-token',
            ])
            ->map(fn (array $values) => Str::limit(implode(', ', $values), 500))
            ->all();
    }

    private function buildPreviousExceptionChain(Throwable $exception): array
    {
        $previous = [];
        $cursor = $exception->getPrevious();

        while ($cursor !== null && count($previous) < 5) {
            $previous[] = [
                'class' => $cursor::class,
                'message' => $cursor->getMessage(),
                'file' => $cursor->getFile(),
                'line' => $cursor->getLine(),
            ];

            $cursor = $cursor->getPrevious();
        }

        return $previous;
    }
}
