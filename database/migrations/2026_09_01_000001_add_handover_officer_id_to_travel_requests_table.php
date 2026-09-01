<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The handover officer was only ever captured as free text, so the app had no
 * way to reach the person named — no address to notify, and nothing to check a
 * submitted name against. Bind the field to the account it refers to.
 *
 * The column is nullable and the name/title columns stay: permits submitted
 * before this migration have a name and no id, and must keep reading correctly.
 * nullOnDelete rather than cascade — losing the account must never take the
 * travel request with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->foreignId('g_handover_officer_id')
                ->nullable()
                ->after('g_handover_officer_title')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('g_handover_officer_id');
        });
    }
};
