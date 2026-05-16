<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support ALTER COLUMN for enum changes.
        // We recreate the column via a raw update instead.
        // The status column is already a string under the hood in SQLite,
        // so we only need to update the validation constraint in code.
        // For MySQL/PostgreSQL we would run:
        //   DB::statement("ALTER TABLE travel_requests MODIFY COLUMN status ENUM(...)");
        // Since we use SQLite we just add the indexes here.

        Schema::table('travel_requests', function (Blueprint $table) {
            $table->index('status',               'idx_tr_status');
            $table->index('current_approver_id',  'idx_tr_current_approver');
            $table->index('requester_id',         'idx_tr_requester');
            $table->index('unit_id',              'idx_tr_unit');
            $table->index('submitted_at',         'idx_tr_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropIndex('idx_tr_status');
            $table->dropIndex('idx_tr_current_approver');
            $table->dropIndex('idx_tr_requester');
            $table->dropIndex('idx_tr_unit');
            $table->dropIndex('idx_tr_submitted_at');
        });
    }
};
