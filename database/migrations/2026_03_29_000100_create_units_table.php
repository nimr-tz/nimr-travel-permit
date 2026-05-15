<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            // hq_standalone = units reporting directly to DG (Internal Audit, Legal, ICT, etc.)
            // hq_directorate = Directorates at HQ
            // hq_section = Sections under a Directorate
            // research_centre = one of the 7 research centres
            $table->enum('type', ['hq_standalone', 'hq_directorate', 'hq_section', 'research_centre']);
            $table->foreignId('parent_id')->nullable()->constrained('units')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
