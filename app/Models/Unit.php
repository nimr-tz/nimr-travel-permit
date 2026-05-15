<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function travelRequests(): HasMany
    {
        return $this->hasMany(TravelRequest::class);
    }

    public function isResearchCentre(): bool
    {
        return $this->type === 'research_centre';
    }

    public function isHq(): bool
    {
        return in_array($this->type, ['hq_standalone', 'hq_directorate', 'hq_section']);
    }
}
