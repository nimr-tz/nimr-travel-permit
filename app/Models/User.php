<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'unit_id',
        'phone',
        'staff_number',
        'job_title',
        'role',
        'supervisor_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    public function travelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class, 'requester_id');
    }

    public function approvalActions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class, 'actor_id');
    }

    public function isDirectorGeneral(): bool
    {
        return $this->role === 'director_general';
    }

    public function isCentreManager(): bool
    {
        return $this->role === 'centre_manager';
    }

    public function isHr(): bool
    {
        return $this->role === 'hr';
    }

    public function isApprover(): bool
    {
        return !$this->isHr() && !$this->isDirectorGeneral() && $this->role !== 'staff';
    }
}
