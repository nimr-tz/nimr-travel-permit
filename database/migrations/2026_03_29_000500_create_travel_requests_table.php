<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            // Points to the user who needs to act next (null if draft or fully resolved)
            $table->foreignId('current_approver_id')->nullable()->constrained('users')->nullOnDelete();

            // --- Section B: Taarifa za Mtumishi Anayesafiri ---
            $table->string('b_applicant_name');
            $table->string('b_phone')->nullable();
            $table->string('b_email')->nullable();
            $table->string('b_position')->nullable();           // Cheo
            $table->string('b_destination')->nullable();        // Mikoa/Mkoa/Wilaya anapokwenda
            $table->date('b_departure_date')->nullable();       // Tarehe ya Kuondoka
            $table->date('b_return_date')->nullable();          // Tarehe ya Kurudi

            // --- Section C: Chanzo cha Safari ---
            $table->text('c_travel_source')->nullable();        // Who initiated, why, attach letter

            // --- Section D: Faida ya Safari na Athari ---
            $table->text('d_benefit_to_institution')->nullable();
            $table->text('d_benefit_to_nation')->nullable();
            $table->text('d_consequences_if_rejected')->nullable();

            // --- Section E: Gharama za Safari ---
            // E(i) Transport costs
            $table->text('e_transport_costs')->nullable();      // Gharama za Usafiri (Taja kiwango)

            // E(ii) Allowances - Posho zote (a, b, c, d)
            $table->string('e_allowance_a')->nullable();
            $table->string('e_allowance_b')->nullable();
            $table->string('e_allowance_c')->nullable();
            $table->string('e_allowance_d')->nullable();
            $table->string('e_budget_line')->nullable();        // Kifungu cha safari kinachohusika

            // Who pays - Donor costs (i, ii, iii)
            $table->string('e_donor_cost_i')->nullable();
            $table->string('e_donor_cost_ii')->nullable();
            $table->string('e_donor_cost_iii')->nullable();

            // Who pays - Government costs (i, ii, iii)
            $table->string('e_govt_cost_i')->nullable();
            $table->string('e_govt_cost_ii')->nullable();
            $table->string('e_govt_cost_iii')->nullable();

            // E(iv) Other costs
            $table->text('e_other_costs')->nullable();

            // --- Section F: Manufaa ya Safari za Nyuma (Impact Assessment) ---
            $table->text('f_previous_travel_impact')->nullable();
            $table->date('f_traveller_signed_date')->nullable();

            // --- Section G: Handover Note ---
            $table->string('g_handover_officer_name')->nullable();
            $table->string('g_handover_officer_title')->nullable();

            // Ordered approval chain stored at submission time: [{stage, approver_id}, ...]
            $table->json('approval_chain')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_requests');
    }
};
