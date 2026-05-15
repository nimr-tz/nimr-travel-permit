<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->string('g_handover_document')->nullable()->after('g_handover_officer_title');
        });
    }

    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropColumn('g_handover_document');
        });
    }
};
