<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TravelRequest extends Model
{
    protected $fillable = [
        'request_number',
        'requester_id',
        'unit_id',
        'status',
        'current_approver_id',

        // Section B
        'b_applicant_name',
        'b_phone',
        'b_email',
        'b_position',
        'b_destination',
        'b_departure_date',
        'b_return_date',

        // Section C
        'c_travel_source',

        // Section D
        'd_benefit_to_institution',
        'd_benefit_to_nation',
        'd_consequences_if_rejected',

        // Section E
        'e_transport_costs',
        'e_allowance_a',
        'e_allowance_b',
        'e_allowance_c',
        'e_allowance_d',
        'e_budget_line',
        'e_donor_cost_i',
        'e_donor_cost_ii',
        'e_donor_cost_iii',
        'e_govt_cost_i',
        'e_govt_cost_ii',
        'e_govt_cost_iii',
        'e_other_costs',

        // Section F
        'f_previous_travel_impact',
        'f_traveller_signed_date',

        // Section G
        'g_handover_officer_name',
        'g_handover_officer_title',
        'g_handover_document',

        'approval_chain',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'b_departure_date'       => 'date',
            'b_return_date'          => 'date',
            'f_traveller_signed_date'=> 'date',
            'approval_chain'         => 'array',
        'submitted_at'           => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    public function approvalActions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class)->orderBy('acted_at');
    }
}
