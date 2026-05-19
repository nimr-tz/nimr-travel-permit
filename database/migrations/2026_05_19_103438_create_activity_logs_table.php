<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable()->index();  // admin who performed the action
            $table->string('action');           // created | updated | deleted
            $table->string('subject_type');     // e.g. App\Models\User
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label')->nullable(); // human-readable name at time of action
            $table->json('changes')->nullable(); // before/after snapshot for updates
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('performed_at');

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
