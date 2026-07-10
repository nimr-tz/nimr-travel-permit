<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->string('travel_report_document')->nullable()->after('g_handover_document');
            $table->string('travel_report_original_name')->nullable()->after('travel_report_document');
            $table->text('travel_report_notes')->nullable()->after('travel_report_original_name');
            $table->timestamp('travel_report_submitted_at')->nullable()->after('travel_report_notes');
        });
    }

    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropColumn([
                'travel_report_document',
                'travel_report_original_name',
                'travel_report_notes',
                'travel_report_submitted_at',
            ]);
        });
    }
};
