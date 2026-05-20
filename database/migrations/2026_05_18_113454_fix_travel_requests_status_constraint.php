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

        // SQLite does not support ALTER COLUMN — recreate with the correct CHECK constraint.
        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement('
            CREATE TABLE "travel_requests_new" (
                "id" integer primary key autoincrement not null,
                "request_number" varchar not null,
                "requester_id" integer,
                "unit_id" integer not null,
                "status" varchar check ("status" in (\'draft\', \'pending\', \'approved\', \'rejected\', \'returned\', \'cancelled\')) not null default \'draft\',
                "current_approver_id" integer,
                "b_applicant_name" varchar not null,
                "b_phone" varchar,
                "b_email" varchar,
                "b_position" varchar,
                "b_destination" varchar,
                "b_departure_date" date,
                "b_return_date" date,
                "c_travel_source" text,
                "d_benefit_to_institution" text,
                "d_benefit_to_nation" text,
                "d_consequences_if_rejected" text,
                "e_transport_costs" text,
                "e_allowance_a" varchar,
                "e_allowance_b" varchar,
                "e_allowance_c" varchar,
                "e_allowance_d" varchar,
                "e_budget_line" varchar,
                "e_donor_cost_i" varchar,
                "e_donor_cost_ii" varchar,
                "e_donor_cost_iii" varchar,
                "e_govt_cost_i" varchar,
                "e_govt_cost_ii" varchar,
                "e_govt_cost_iii" varchar,
                "e_other_costs" text,
                "f_previous_travel_impact" text,
                "f_traveller_signed_date" date,
                "g_handover_officer_name" varchar,
                "g_handover_officer_title" varchar,
                "approval_chain" text,
                "submitted_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                "g_handover_document" varchar,
                foreign key("requester_id") references "users"("id") on delete set null,
                foreign key("unit_id") references "units"("id") on delete restrict,
                foreign key("current_approver_id") references "users"("id") on delete set null
            )
        ');

        DB::statement('INSERT INTO "travel_requests_new" SELECT * FROM "travel_requests"');
        DB::statement('DROP TABLE "travel_requests"');
        DB::statement('ALTER TABLE "travel_requests_new" RENAME TO "travel_requests"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF;');

        DB::statement('
            CREATE TABLE "travel_requests_new" (
                "id" integer primary key autoincrement not null,
                "request_number" varchar not null,
                "requester_id" integer,
                "unit_id" integer not null,
                "status" varchar check ("status" in (\'draft\', \'pending\', \'approved\', \'rejected\')) not null default \'draft\',
                "current_approver_id" integer,
                "b_applicant_name" varchar not null,
                "b_phone" varchar,
                "b_email" varchar,
                "b_position" varchar,
                "b_destination" varchar,
                "b_departure_date" date,
                "b_return_date" date,
                "c_travel_source" text,
                "d_benefit_to_institution" text,
                "d_benefit_to_nation" text,
                "d_consequences_if_rejected" text,
                "e_transport_costs" text,
                "e_allowance_a" varchar,
                "e_allowance_b" varchar,
                "e_allowance_c" varchar,
                "e_allowance_d" varchar,
                "e_budget_line" varchar,
                "e_donor_cost_i" varchar,
                "e_donor_cost_ii" varchar,
                "e_donor_cost_iii" varchar,
                "e_govt_cost_i" varchar,
                "e_govt_cost_ii" varchar,
                "e_govt_cost_iii" varchar,
                "e_other_costs" text,
                "f_previous_travel_impact" text,
                "f_traveller_signed_date" date,
                "g_handover_officer_name" varchar,
                "g_handover_officer_title" varchar,
                "approval_chain" text,
                "submitted_at" datetime,
                "created_at" datetime,
                "updated_at" datetime,
                "g_handover_document" varchar,
                foreign key("requester_id") references "users"("id") on delete set null,
                foreign key("unit_id") references "units"("id") on delete restrict,
                foreign key("current_approver_id") references "users"("id") on delete set null
            )
        ');

        DB::statement('INSERT INTO "travel_requests_new" SELECT * FROM "travel_requests" WHERE "status" NOT IN (\'returned\', \'cancelled\')');
        DB::statement('DROP TABLE "travel_requests"');
        DB::statement('ALTER TABLE "travel_requests_new" RENAME TO "travel_requests"');

        DB::statement('PRAGMA foreign_keys = ON;');
    }
};
