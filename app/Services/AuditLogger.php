<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes the system audit trail (see ActivityLog::EVENTS).
 *
 * Call it after the action has actually happened (after the transaction
 * commits), so the trail never claims something that was rolled back.
 * A failure to write is reported but never breaks the user's action.
 */
class AuditLogger
{
    /** Never stored, wherever they turn up in changes or context. */
    private const SECRET_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
    ];

    /** Bookkeeping columns that only add noise to a diff. */
    private const IGNORED_CHANGE_KEYS = [
        'updated_at',
        'created_at',
        'approval_chain',
        'approval_last_reminded_at',
    ];

    public function log(
        string $event,
        ?Model $subject = null,
        array $changes = [],
        array $context = [],
        ?User $actor = null,
    ): void {
        try {
            $actor ??= auth()->user();
            $request = app()->runningInConsole() ? null : request();

            ActivityLog::create([
                'actor_id'      => $actor?->getKey(),
                'actor_label'   => $actor ? "{$actor->name} ({$actor->email})" : null,
                'unit_id'       => $actor?->unit_id ?? $this->unitOf($subject),
                'action'        => $event,
                'subject_type'  => $subject ? $subject::class : null,
                'subject_id'    => $subject?->getKey(),
                'subject_label' => $subject ? $this->labelOf($subject) : null,
                'changes'       => $this->scrub($changes) ?: null,
                'context'       => $this->scrub(array_filter($context, fn ($v) => $v !== null && $v !== '')) ?: null,
                'ip_address'    => $request?->ip(),
                'user_agent'    => $request ? Str::limit((string) $request->userAgent(), 500, '') : null,
                'performed_at'  => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Before/after values of the attributes a save just changed.
     * Use straight after save()/update(), while getPrevious() still holds them.
     */
    public function changesOf(Model $model): array
    {
        $after = Arr::except($model->getChanges(), self::IGNORED_CHANGE_KEYS);

        if ($after === []) {
            return [];
        }

        return [
            'before' => Arr::only($model->getPrevious(), array_keys($after)),
            'after'  => $after,
        ];
    }

    /**
     * Before/after of two attribute snapshots, keeping only what differs.
     */
    public function diff(array $before, array $after): array
    {
        $changed = array_keys(array_filter(
            $after,
            fn ($value, $key) => ($before[$key] ?? null) != $value,
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($changed === []) {
            return [];
        }

        return [
            'before' => Arr::only($before, $changed),
            'after'  => Arr::only($after, $changed),
        ];
    }

    private function labelOf(Model $subject): string
    {
        return match (true) {
            $subject instanceof User          => $subject->getActivityLabel(),
            $subject instanceof TravelRequest => $subject->request_number
                ?: 'Travel request #'.$subject->getKey(),
            default => class_basename($subject).' #'.$subject->getKey(),
        };
    }

    private function unitOf(?Model $subject): ?int
    {
        return match (true) {
            $subject instanceof User, $subject instanceof TravelRequest => $subject->unit_id,
            default => null,
        };
    }

    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array($key, self::SECRET_KEYS, true)) {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->scrub($value);
            }
        }

        return $data;
    }
}
