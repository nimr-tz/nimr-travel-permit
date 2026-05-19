<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'unit_id',
        'phone',
        'job_title',
        'avatar_path',
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

    public function getActivityLabel(): string
    {
        return "{$this->name} ({$this->email})";
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

    public function isSystemAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    public function isGlobalSystemAdmin(): bool
    {
        return $this->isSystemAdmin() && !$this->unit?->isResearchCentre();
    }

    public function isCentreSystemAdmin(): bool
    {
        return $this->isSystemAdmin() && $this->unit?->isResearchCentre();
    }

    public function isApprover(): bool
    {
        return !$this->isHr() && !$this->isSystemAdmin() && $this->role !== 'staff';
    }
}
