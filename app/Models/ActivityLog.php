<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'changes',
        'ip_address',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'changes'       => 'array',
            'performed_at'  => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(string $action, Model $subject, array $changes = []): void
    {
        static::create([
            'actor_id'      => auth()->id(),
            'action'        => $action,
            'subject_type'  => get_class($subject),
            'subject_id'    => $subject->getKey(),
            'subject_label' => method_exists($subject, 'getActivityLabel') ? $subject->getActivityLabel() : (string) $subject->getKey(),
            'changes'       => $changes ?: null,
            'ip_address'    => request()->ip(),
            'performed_at'  => now(),
        ]);
    }
}
