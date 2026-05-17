<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // Which stage in the approval chain:
            // supervisor = immediate supervisor / head of section / unit manager (Section H equivalent)
            // director   = directorate director or second-level centre approver (Section I equivalent)
            // final      = Director General or Centre Manager issues the permit (Section J)
            // hr         = HR receives for information (Section K)
            $table->enum('stage', ['supervisor', 'director', 'final', 'hr']);

            $table->enum('decision', ['approved', 'rejected', 'returned']);
            $table->text('comment')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
