<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalAction extends Model
{
    protected $fillable = [
        'travel_request_id',
        'actor_id',
        'stage',
        'decision',
        'comment',
        'acted_at',
    ];

    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
        ];
    }

    public function travelRequest(): BelongsTo
    {
        return $this->belongsTo(TravelRequest::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function stageLabel(): string
    {
        return match($this->stage) {
            'supervisor' => 'Supervisor / Head of Section',
            'director'   => 'Director',
            'final'      => 'Final Approval (Director General / Centre Manager)',
            'hr'         => 'HR (For Information)',
            default      => $this->stage,
        };
    }
}
