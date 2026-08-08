<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section C tells the applicant to attach the invitation letter, but until now
 * the form had nowhere to put it — the single upload slot belongs to the
 * handover note. Optional: not every trip is invitation-led.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->string('c_invitation_document')->nullable()->after('c_travel_source');
            $table->string('c_invitation_original_name')->nullable()->after('c_invitation_document');
        });
    }

    public function down(): void
    {
        Schema::table('travel_requests', function (Blueprint $table) {
            $table->dropColumn(['c_invitation_document', 'c_invitation_original_name']);
        });
    }
};
