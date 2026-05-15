<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('id')->constrained('units')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('staff_number')->nullable()->after('phone');
            $table->string('job_title')->nullable()->after('staff_number');
            // staff           = regular employee
            // head            = Head of Section (HQ)
            // manager         = Manager of standalone unit / section within centre
            // director        = Director of a Directorate (HQ)
            // centre_manager  = Manager of a Research Centre
            // director_general= The Director General
            // hr              = HR staff (receives copies, no approval)
            $table->enum('role', ['staff', 'head', 'manager', 'director', 'centre_manager', 'director_general', 'hr'])
                  ->default('staff')
                  ->after('job_title');
            // For research centre employees who have a supervisor below the centre manager
            $table->foreignId('supervisor_id')->nullable()->after('role')->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn(['phone', 'staff_number', 'job_title', 'role', 'is_active']);
        });
    }
};
