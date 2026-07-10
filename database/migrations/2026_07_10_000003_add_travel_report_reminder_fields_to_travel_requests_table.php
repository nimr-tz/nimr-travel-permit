<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->timestamp('travel_report_last_reminded_at')->nullable()->after('travel_report_submitted_at');
            $table->unsignedInteger('travel_report_reminder_count')->default(0)->after('travel_report_last_reminded_at');
        });
    }

    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropColumn([
                'travel_report_last_reminded_at',
                'travel_report_reminder_count',
            ]);
        });
    }
};
