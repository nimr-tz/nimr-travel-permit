<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dgId = DB::table('users')
            ->where('role', 'director_general')
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id');

        if (!$dgId) {
            return;
        }

        $researchCentreIds = DB::table('units')
            ->where('type', 'research_centre')
            ->pluck('id');

        $hqStandaloneIds = DB::table('units')
            ->where('type', 'hq_standalone')
            ->pluck('id');

        $hqDirectorateIds = DB::table('units')
            ->where('type', 'hq_directorate')
            ->pluck('id');

        DB::table('users')
            ->whereIn('unit_id', $researchCentreIds)
            ->where('role', 'centre_manager')
            ->update(['supervisor_id' => $dgId]);

        DB::table('users')
            ->whereIn('unit_id', $hqStandaloneIds)
            ->where('role', 'manager')
            ->update(['supervisor_id' => $dgId]);

        DB::table('users')
            ->whereIn('unit_id', $hqDirectorateIds)
            ->where('role', 'director')
            ->update(['supervisor_id' => $dgId]);
    }

    public function down(): void
    {
        $dgIds = DB::table('users')
            ->where('role', 'director_general')
            ->pluck('id');

        if ($dgIds->isEmpty()) {
            return;
        }

        $researchCentreIds = DB::table('units')
            ->where('type', 'research_centre')
            ->pluck('id');

        $hqStandaloneIds = DB::table('units')
            ->where('type', 'hq_standalone')
            ->pluck('id');

        $hqDirectorateIds = DB::table('units')
            ->where('type', 'hq_directorate')
            ->pluck('id');

        DB::table('users')
            ->whereIn('unit_id', $researchCentreIds)
            ->where('role', 'centre_manager')
            ->whereIn('supervisor_id', $dgIds)
            ->update(['supervisor_id' => null]);

        DB::table('users')
            ->whereIn('unit_id', $hqStandaloneIds)
            ->where('role', 'manager')
            ->whereIn('supervisor_id', $dgIds)
            ->update(['supervisor_id' => null]);

        DB::table('users')
            ->whereIn('unit_id', $hqDirectorateIds)
            ->where('role', 'director')
            ->whereIn('supervisor_id', $dgIds)
            ->update(['supervisor_id' => null]);
    }
};
