<?php

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * HR is no longer an active approver in the chain.
     * 1. Auto-approves any requests currently stuck at the HR stage.
     * 2. Strips the HR step from stored chains on pending/returned/draft requests.
     */
    public function up(): void
    {
        $hrUserIds = User::where('role', 'hr')->pluck('id');

        TravelRequest::whereIn('current_approver_id', $hrUserIds)
            ->where('status', TravelRequest::STATUS_PENDING)
            ->each(function (TravelRequest $tr) {
                $tr->update([
                    'status'              => TravelRequest::STATUS_APPROVED,
                    'current_approver_id' => null,
                ]);
            });

        TravelRequest::whereIn('status', [
            TravelRequest::STATUS_PENDING,
            TravelRequest::STATUS_RETURNED,
            TravelRequest::STATUS_DRAFT,
        ])->each(function (TravelRequest $tr) {
            if (!is_array($tr->approval_chain)) {
                return;
            }

            $cleaned = collect($tr->approval_chain)
                ->reject(fn($step) => ($step['stage'] ?? '') === 'hr')
                ->values()
                ->all();

            $tr->update(['approval_chain' => $cleaned]);
        });
    }

    public function down(): void
    {
        // Not reversible.
    }
};
