<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One entry in the system audit trail. Written only through AuditLogger.
 *
 * Entries are append-only: the model refuses to update or delete them, and
 * there is no route that tries to.
 */
class ActivityLog extends Model
{
    public $timestamps = false;

    /**
     * Every event the trail records, grouped as the audit page filters them.
     * A new event must be added here and to lang/{en,sw}/audit.php.
     */
    public const EVENTS = [
        'auth' => [
            'auth.login',
            'auth.login_failed',
            'auth.lockout',
            'auth.logout',
            'auth.registered',
            'auth.email_verified',
            'auth.password_reset_requested',
            'auth.password_reset',
            'auth.password_changed',
        ],
        'account' => [
            'account.profile_updated',
            'account.supervisor_changed',
            'account.closed',
        ],
        'travel_request' => [
            'travel_request.draft_saved',
            'travel_request.draft_updated',
            'travel_request.submitted',
            'travel_request.resubmitted',
            'travel_request.approved',
            'travel_request.rejected',
            'travel_request.returned',
            'travel_request.cancelled',
            'travel_request.auto_cancelled',
            'travel_request.report_uploaded',
            'travel_request.report_unlocked',
        ],
        'download' => [
            'download.permit_pdf',
            'download.permit_print',
            'download.handover_document',
            'download.invitation_letter',
            'download.travel_report',
            'download.hr_report_export',
            'download.audit_export',
        ],
        'admin' => [
            'user.created',
            'user.updated',
            'user.deactivated',
            'user.reactivated',
        ],
    ];

    /** Events worth drawing an admin's eye to in the list. */
    public const WARNING_EVENTS = [
        'auth.login_failed',
        'auth.lockout',
        'travel_request.rejected',
        'user.deactivated',
        'account.closed',
    ];

    protected $fillable = [
        'actor_id',
        'actor_label',
        'unit_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'changes',
        'context',
        'ip_address',
        'user_agent',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'changes'      => 'array',
            'context'      => 'array',
            'performed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit log entries cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit log entries cannot be deleted.'));
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public static function allEvents(): array
    {
        return array_merge(...array_values(self::EVENTS));
    }

    public static function categoryOf(string $event): ?string
    {
        foreach (self::EVENTS as $category => $events) {
            if (in_array($event, $events, true)) {
                return $category;
            }
        }

        return null;
    }

    public function category(): ?string
    {
        return self::categoryOf($this->action);
    }

    public function isWarning(): bool
    {
        return in_array($this->action, self::WARNING_EVENTS, true);
    }

    /**
     * HQ system admins see the whole institute. A centre's system admin sees
     * only activity that belongs to their own centre.
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if ($viewer->isCentreSystemAdmin()) {
            $query->where('unit_id', $viewer->unit_id);
        }

        return $query;
    }
}
