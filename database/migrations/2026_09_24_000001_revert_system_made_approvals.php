<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * An earlier release closed requests stuck at the final approver out by
 * writing a final-stage "approved" action with no actor and flipping the
 * request to approved. That approval was never the DG's, so this undoes it:
 * the request goes back to waiting on the final approver exactly as if the
 * system had never touched it. Uploaded reports are left alone.
 *
 * A request whose final approver has since recorded a real decision keeps
 * that decision and status; only the system-made row is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $systemActions = DB::table('approval_actions')
            ->whereNull('actor_id')
            ->where('stage', 'final')
            ->where('decision', 'approved')
            ->where('comment', 'like', 'Auto-approved:%')
            ->get();

        foreach ($systemActions as $action) {
            DB::transaction(function () use ($action) {
                $realDecisionExists = DB::table('approval_actions')
                    ->where('travel_request_id', $action->travel_request_id)
                    ->where('stage', 'final')
                    ->whereNotNull('actor_id')
                    ->exists();

                DB::table('approval_actions')->where('id', $action->id)->delete();

                if ($realDecisionExists) {
                    return;
                }

                $request = DB::table('travel_requests')->where('id', $action->travel_request_id)->first();

                if (! $request || $request->status !== 'approved') {
                    return;
                }

                $chain = json_decode($request->approval_chain ?? 'null', true) ?: [];
                $final = collect($chain)->firstWhere('stage', 'final');

                if (! $final) {
                    return;
                }

                DB::table('travel_requests')->where('id', $request->id)->update([
                    'status' => 'pending',
                    'current_approver_id' => $final['approver_id'],
                ]);
            });
        }
    }

    public function down(): void
    {
        // The removed approvals were never legitimate; nothing to restore.
    }
};
