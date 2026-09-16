<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * activity_logs used to record only an admin creating or editing a user. It now
 * backs the system-wide audit trail, so it needs to hold events with no subject
 * (a failed sign-in for an unknown email), who the actor was at the time, the
 * unit the event belongs to (for centre-scoped admins) and extra context.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->change();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('actor_label')->nullable()->after('actor_id');
            $table->unsignedBigInteger('unit_id')->nullable()->after('actor_label');
            $table->json('context')->nullable()->after('changes');
            $table->string('user_agent', 512)->nullable()->after('ip_address');

            $table->index('unit_id');
            $table->index('action');
            $table->index('performed_at');
        });

        // Existing rows were all admin user management; give them the new names
        // and fill in the actor snapshot from the accounts as they are today.
        foreach (['created', 'updated'] as $action) {
            DB::table('activity_logs')
                ->where('subject_type', 'App\\Models\\User')
                ->where('action', $action)
                ->update(['action' => "user.{$action}"]);
        }

        DB::table('activity_logs')->whereNotNull('actor_id')->orderBy('id')->each(function ($row) {
            $actor = DB::table('users')->where('id', $row->actor_id)->first(['name', 'email', 'unit_id']);

            if ($actor) {
                DB::table('activity_logs')->where('id', $row->id)->update([
                    'actor_label' => "{$actor->name} ({$actor->email})",
                    'unit_id'     => $actor->unit_id,
                ]);
            }
        });
    }

    public function down(): void
    {
        foreach (['created', 'updated'] as $action) {
            DB::table('activity_logs')->where('action', "user.{$action}")->update(['action' => $action]);
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['unit_id']);
            $table->dropIndex(['action']);
            $table->dropIndex(['performed_at']);
            $table->dropColumn(['actor_label', 'unit_id', 'context', 'user_agent']);
        });
    }
};
