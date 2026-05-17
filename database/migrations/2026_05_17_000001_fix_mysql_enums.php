<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE travel_requests MODIFY COLUMN status ENUM('draft','pending','approved','rejected','returned','cancelled') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE approval_actions MODIFY COLUMN decision ENUM('approved','rejected','returned') NOT NULL");
        }
        // SQLite stores enums as plain strings — validation happens in PHP, no ALTER needed.
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE travel_requests MODIFY COLUMN status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE approval_actions MODIFY COLUMN decision ENUM('approved','rejected') NOT NULL");
        }
    }
};
