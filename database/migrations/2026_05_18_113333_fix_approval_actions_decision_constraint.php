<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        // SQLite does not support ALTER COLUMN — recreate the table with the correct CHECK constraint.
        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement('
            CREATE TABLE "approval_actions_new" (
                "id" integer primary key autoincrement not null,
                "travel_request_id" integer not null,
                "actor_id" integer,
                "stage" varchar check ("stage" in (\'supervisor\', \'director\', \'final\', \'hr\')) not null,
                "decision" varchar check ("decision" in (\'approved\', \'rejected\', \'returned\')) not null,
                "comment" text,
                "acted_at" datetime not null,
                "created_at" datetime,
                "updated_at" datetime,
                foreign key("travel_request_id") references "travel_requests"("id") on delete cascade,
                foreign key("actor_id") references "users"("id") on delete set null
            )
        ');

        DB::statement('INSERT INTO "approval_actions_new" SELECT * FROM "approval_actions"');
        DB::statement('DROP TABLE "approval_actions"');
        DB::statement('ALTER TABLE "approval_actions_new" RENAME TO "approval_actions"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement('
            CREATE TABLE "approval_actions_new" (
                "id" integer primary key autoincrement not null,
                "travel_request_id" integer not null,
                "actor_id" integer,
                "stage" varchar check ("stage" in (\'supervisor\', \'director\', \'final\', \'hr\')) not null,
                "decision" varchar check ("decision" in (\'approved\', \'rejected\')) not null,
                "comment" text,
                "acted_at" datetime not null,
                "created_at" datetime,
                "updated_at" datetime,
                foreign key("travel_request_id") references "travel_requests"("id") on delete cascade,
                foreign key("actor_id") references "users"("id") on delete set null
            )
        ');

        DB::statement('INSERT INTO "approval_actions_new" SELECT * FROM "approval_actions" WHERE "decision" != \'returned\'');
        DB::statement('DROP TABLE "approval_actions"');
        DB::statement('ALTER TABLE "approval_actions_new" RENAME TO "approval_actions"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }
};
