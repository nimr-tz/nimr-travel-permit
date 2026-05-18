<?php

namespace Database\Seeders;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\ApprovalChainService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TravelRequestSeeder extends Seeder
{
    // -----------------------------------------------------------------------
    // Realistic Tanzanian names for variety
    // -----------------------------------------------------------------------
    private array $firstNames = [
        'Asha', 'Grace', 'Peter', 'Mary', 'John', 'Fatuma', 'Joseph', 'Amina',
        'David', 'Rehema', 'Charles', 'Zuhura', 'Emmanuel', 'Neema', 'Hamisi',
        'Joyce', 'Hassan', 'Sophia', 'Francis', 'Salma', 'Bernard', 'Zawadi',
        'Patrick', 'Mwanaisha', 'Robert', 'Esther', 'Rashid', 'Winfrida',
        'Daniel', 'Perpetua',
    ];

    private array $lastNames = [
        'Mwangaza', 'Kileo', 'Mkapa', 'Sengo', 'Mwaikambo', 'Maganga',
        'Chombo', 'Mwenda', 'Kisanga', 'Nkemdirim', 'Mapesa', 'Shayo',
        'Rugumyamheto', 'Mhando', 'Kyaruzi', 'Nkosi', 'Mzinga', 'Tarimo',
        'Mramba', 'Mwakyusa', 'Kaunda', 'Sumari', 'Mwasumbi', 'Lema',
    ];

    private array $destinations = [
        'Dar es Salaam', 'Arusha', 'Mwanza', 'Dodoma', 'Mbeya', 'Tanga',
        'Morogoro', 'Tabora', 'Iringa', 'Kigoma', 'Lindi', 'Moshi',
        'Shinyanga', 'Singida', 'Songea', 'Musoma', 'Bukoba', 'Sumbawanga',
        'Nairobi, Kenya', 'Kampala, Uganda', 'Geneva, Switzerland',
        'Johannesburg, South Africa',
    ];

    private array $purposes = [
        'Field supervision of TB prevalence sub-study sites in the district.',
        'Attend national workshop on research data management and biostatistics.',
        'Quarterly monitoring visit to community health intervention project sites.',
        'Participate in East African regional health systems conference.',
        'Conduct structured interviews for malaria community health survey.',
        'Laboratory capacity building training at partner institution.',
        'Grant proposal development and budget planning meeting.',
        'Stakeholder consultation on HIV/AIDS control programme strategy.',
        'Site inspection and quality control audit for GCP compliance.',
        'Training on new rapid diagnostic equipment and procedures.',
        'Dissemination of research findings to county health stakeholders.',
        'Collection of biological specimens for multi-centre study.',
        'Inter-agency coordination meeting on neglected tropical diseases.',
        'Mentorship and technical support visit to affiliated facility.',
        'Final evaluation of community intervention programme outcomes.',
    ];

    private array $budgetLines = [
        'Research & Development — Field Operations',
        'Research & Development — Training and Capacity Building',
        'Research & Development — Conferences and Dissemination',
        'Operational — Staff Development',
        'Grant-funded — NIMR-MoH Joint Programme',
    ];

    private int $seq = 0;

    // -----------------------------------------------------------------------

    public function run(): void
    {
        $service = new ApprovalChainService();

        // Step 1: ensure we have enough users across units
        $this->ensureAdditionalUsers();

        // Step 2: collect all eligible requesters (everyone except DG and HR)
        $requesters = User::whereNotIn('role', ['director_general', 'hr'])
            ->where('is_active', true)
            ->with('unit')
            ->get();

        if ($requesters->isEmpty()) {
            $this->command->warn('No eligible requesters found — nothing seeded.');
            return;
        }

        // Initialise sequence from existing count
        $this->seq = TravelRequest::max('id') ?? 0;

        $created   = 0;
        $skipped   = 0;

        foreach ($requesters as $user) {
            if (!$user->unit) {
                $skipped++;
                continue;
            }

            try {
                $chain = $service->buildChain($user);
            } catch (\RuntimeException $e) {
                $this->command->warn("  Skipped [{$user->email}]: {$e->getMessage()}");
                $skipped++;
                continue;
            }

            // Each user gets between 3 and 7 requests
            $requestCount = rand(3, 7);

            for ($i = 0; $i < $requestCount; $i++) {
                $this->createRequest($user, $chain);
                $created++;
            }
        }

        $this->command->info("Done — {$created} travel requests seeded across {$requesters->count()} users ({$skipped} users skipped).");
    }

    // -----------------------------------------------------------------------
    // Core request factory
    // -----------------------------------------------------------------------

    private function createRequest(User $user, array $chain): void
    {
        $this->seq++;

        // Status distribution: ~18% draft, ~32% pending, ~28% approved,
        //                       ~10% returned, ~7% rejected, ~5% cancelled
        $roll = rand(1, 100);
        $status = match(true) {
            $roll <= 18  => TravelRequest::STATUS_DRAFT,
            $roll <= 50  => TravelRequest::STATUS_PENDING,
            $roll <= 78  => TravelRequest::STATUS_APPROVED,
            $roll <= 88  => TravelRequest::STATUS_RETURNED,
            $roll <= 95  => TravelRequest::STATUS_REJECTED,
            default      => TravelRequest::STATUS_CANCELLED,
        };

        // Departure date varies by status
        $depDays = match($status) {
            TravelRequest::STATUS_APPROVED  => rand(-90, -10),
            TravelRequest::STATUS_REJECTED  => rand(-120, -20),
            TravelRequest::STATUS_CANCELLED => rand(-30, 30),
            TravelRequest::STATUS_RETURNED  => rand(-20, 20),
            TravelRequest::STATUS_PENDING   => rand(-10, 35),
            default                         => rand(7, 60),  // draft
        };

        $departure   = now()->addDays($depDays)->startOfDay();
        $returnDate  = $departure->copy()->addDays(rand(3, 10));
        $destination = $this->destinations[array_rand($this->destinations)];
        $purpose     = $this->purposes[array_rand($this->purposes)];
        $budgetLine  = $this->budgetLines[array_rand($this->budgetLines)];
        $tripDays    = max(1, (int) $departure->diffInDays($returnDate));

        // Determine chain position and timestamps
        $currentApproverId = null;
        $submittedAt       = null;
        $approvalChain     = $chain;
        $pendingStepIdx    = null;

        switch ($status) {
            case TravelRequest::STATUS_PENDING:
                // Pick a random step for pipeline variety — but prior steps MUST have
                // approved actions created by seedActions() so the chain is coherent.
                $pendingStepIdx    = rand(0, count($chain) - 1);
                $currentApproverId = $chain[$pendingStepIdx]['approver_id'];
                $submittedAt       = now()->subDays(rand(1, 21));
                break;

            case TravelRequest::STATUS_APPROVED:
            case TravelRequest::STATUS_REJECTED:
                $submittedAt = now()->subDays(rand(14, 120));
                break;

            case TravelRequest::STATUS_RETURNED:
                // Requester needs to edit & resubmit — no submitted_at, no approver
                $submittedAt   = null;
                $approvalChain = $chain; // chain is preserved
                break;

            case TravelRequest::STATUS_DRAFT:
            case TravelRequest::STATUS_CANCELLED:
                $approvalChain = null;
                break;
        }

        $tr = TravelRequest::create([
            'request_number'      => 'NIMR-ITP-' . now()->year . '-' . str_pad($this->seq, 4, '0', STR_PAD_LEFT),
            'requester_id'        => $user->id,
            'unit_id'             => $user->unit_id,
            'status'              => $status,
            'current_approver_id' => $currentApproverId,
            'approval_chain'      => $approvalChain,
            'submitted_at'        => $submittedAt,

            // Section B — Applicant details
            'b_applicant_name' => $user->name,
            'b_phone'          => '+255 7' . rand(10, 99) . ' ' . rand(100, 999) . ' ' . rand(100, 999),
            'b_email'          => $user->email,
            'b_position'       => $user->job_title ?? 'Research Officer',
            'b_destination'    => $destination,
            'b_departure_date' => $departure->toDateString(),
            'b_return_date'    => $returnDate->toDateString(),

            // Section C — Travel justification
            'c_travel_source' => $purpose,

            // Section D — Institutional benefit
            'd_benefit_to_institution'   => $purpose,
            'd_benefit_to_nation'        => 'Supports national health research agenda and strengthens public health capacity.',
            'd_consequences_if_rejected' => 'Delays in project milestones and potential loss of collaborative research funding.',

            // Section E — Costs
            'e_transport_costs' => number_format(rand(100, 600) * 1000) . '/=',
            'e_allowance_a'     => number_format(rand(50, 80) * 1000) . '/= per day × ' . $tripDays . ' days',
            'e_budget_line'     => $budgetLine,
            'e_govt_cost_i'     => number_format(rand(100, 400) * 1000) . '/=',
            'e_govt_cost_ii'    => number_format(rand(50, 80) * 1000 * $tripDays) . '/=',

            // Section F — Previous travel
            'f_previous_travel_impact' => 'Previous field work contributed to the completion of a collaborative research protocol currently under review by the IRB.',
            'f_traveller_signed_date'  => ($submittedAt ?? now())->toDateString(),

            // Section G — Handover
            'g_handover_officer_name'  => $this->randomName(),
            'g_handover_officer_title' => 'Senior Research Officer',
            'g_handover_document'      => 'Detailed handover notes prepared and submitted to the section head prior to departure.',
        ]);

        // Seed ApprovalAction records
        $this->seedActions($tr, $chain, $status, $submittedAt, $pendingStepIdx);
    }

    // -----------------------------------------------------------------------
    // ApprovalAction records for realism
    // -----------------------------------------------------------------------

    private function seedActions(TravelRequest $tr, array $chain, string $status, ?\Carbon\Carbon $submittedAt, ?int $pendingStepIdx = null): void
    {
        if (in_array($status, [TravelRequest::STATUS_DRAFT, TravelRequest::STATUS_CANCELLED])) {
            return;
        }

        $actionDate = ($submittedAt ?? now()->subDays(30))->copy();

        // For pending requests, create approved actions for every step BEFORE the current one.
        // This ensures the request legitimately reached the current approver through the chain.
        if ($status === TravelRequest::STATUS_PENDING) {
            for ($i = 0; $i < ($pendingStepIdx ?? 0); $i++) {
                $actionDate->addDays(rand(1, 3));
                ApprovalAction::create([
                    'travel_request_id' => $tr->id,
                    'actor_id'          => $chain[$i]['approver_id'],
                    'stage'             => $chain[$i]['stage'],
                    'decision'          => 'approved',
                    'comment'           => $this->approveComment(),
                    'acted_at'          => $actionDate->copy(),
                ]);
            }
            return;
        }

        switch ($status) {
            case TravelRequest::STATUS_APPROVED:
                // Every step in the chain was approved
                foreach ($chain as $step) {
                    $actionDate->addDays(rand(1, 4));
                    ApprovalAction::create([
                        'travel_request_id' => $tr->id,
                        'actor_id'          => $step['approver_id'],
                        'stage'             => $step['stage'],
                        'decision'          => 'approved',
                        'comment'           => $this->approveComment(),
                        'acted_at'          => $actionDate->copy(),
                    ]);
                }
                break;

            case TravelRequest::STATUS_REJECTED:
                // Rejected at a random step (not necessarily the first)
                $rejectAt = rand(0, count($chain) - 1);
                foreach ($chain as $idx => $step) {
                    $actionDate->addDays(rand(1, 3));
                    if ($idx < $rejectAt) {
                        ApprovalAction::create([
                            'travel_request_id' => $tr->id,
                            'actor_id'          => $step['approver_id'],
                            'stage'             => $step['stage'],
                            'decision'          => 'approved',
                            'comment'           => $this->approveComment(),
                            'acted_at'          => $actionDate->copy(),
                        ]);
                    } else {
                        ApprovalAction::create([
                            'travel_request_id' => $tr->id,
                            'actor_id'          => $step['approver_id'],
                            'stage'             => $step['stage'],
                            'decision'          => 'rejected',
                            'comment'           => $this->rejectComment(),
                            'acted_at'          => $actionDate->copy(),
                        ]);
                        break;
                    }
                }
                break;

            case TravelRequest::STATUS_RETURNED:
                // Returned by the first approver
                $actionDate->addDays(rand(1, 5));
                ApprovalAction::create([
                    'travel_request_id' => $tr->id,
                    'actor_id'          => $chain[0]['approver_id'],
                    'stage'             => $chain[0]['stage'],
                    'decision'          => 'returned',
                    'comment'           => $this->returnComment(),
                    'acted_at'          => $actionDate->copy(),
                ]);
                break;
        }
    }

    // -----------------------------------------------------------------------
    // Ensure enough users exist across units for a realistic dataset
    // -----------------------------------------------------------------------

    private function ensureAdditionalUsers(): void
    {
        // Research centres — add 2 staff each (CM already exists from DatabaseSeeder)
        $centreCodes = ['ARC', 'DRC', 'MTMC', 'MBRC', 'MRC', 'MWRC', 'TRC'];
        $staffIndex  = 1;

        foreach ($centreCodes as $code) {
            $unit = Unit::where('code', $code)->first();
            if (!$unit) continue;

            for ($n = 1; $n <= 2; $n++) {
                $slug  = strtolower($code);
                $name  = $this->randomName();
                $email = "staff.{$slug}.{$n}@nimr.or.tz";

                User::firstOrCreate(['email' => $email], [
                    'name'         => $name,
                    'password'     => Hash::make('password'),
                    'unit_id'      => $unit->id,
                    'job_title'    => 'Research Officer',
                    'role'         => 'staff',
                    'staff_number' => strtoupper($code) . '-STF-' . str_pad($staffIndex++, 3, '0', STR_PAD_LEFT),
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]);
            }

            // Also add one manager per centre so staff can optionally have supervisors
            $managerEmail = "mgr.{$slug}@nimr.or.tz";
            User::firstOrCreate(['email' => $managerEmail], [
                'name'         => $this->randomName(),
                'password'     => Hash::make('password'),
                'unit_id'      => $unit->id,
                'job_title'    => 'Senior Research Scientist',
                'role'         => 'manager',
                'staff_number' => strtoupper($code) . '-MGR-001',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        }

        // HQ Standalone units — add manager + staff to ICT and Legal
        foreach ([['ICT', 'ICT Officer'], ['LSU', 'Legal Officer']] as [$code, $title]) {
            $unit = Unit::where('code', $code)->first();
            if (!$unit) continue;

            $slug = strtolower(str_replace(' ', '_', $code));

            $mgrEmail = "mgr.{$slug}@nimr.or.tz";
            User::firstOrCreate(['email' => $mgrEmail], [
                'name'         => $this->randomName(),
                'password'     => Hash::make('password'),
                'unit_id'      => $unit->id,
                'job_title'    => 'Unit Manager',
                'role'         => 'manager',
                'staff_number' => strtoupper($code) . '-MGR-001',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);

            $staffEmail = "staff.{$slug}@nimr.or.tz";
            User::firstOrCreate(['email' => $staffEmail], [
                'name'         => $this->randomName(),
                'password'     => Hash::make('password'),
                'unit_id'      => $unit->id,
                'job_title'    => $title,
                'role'         => 'staff',
                'staff_number' => strtoupper($code) . '-STF-001',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        }

        // HQ sections with parent directorates — need directors + heads + staff
        // RCPD sections
        $rcpd = Unit::where('code', 'RCPD')->first();
        if ($rcpd) {
            User::firstOrCreate(['email' => 'director.rcpd@nimr.or.tz'], [
                'name'         => $this->randomName(),
                'password'     => Hash::make('password'),
                'unit_id'      => $rcpd->id,
                'job_title'    => 'Director of Research Coordination',
                'role'         => 'director',
                'staff_number' => 'RCPD-DIR-001',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);

            foreach (['PHPS', 'HSPTRS', 'ICTTS'] as $sCode) {
                $section = Unit::where('code', $sCode)->first();
                if (!$section) continue;
                $slug = strtolower($sCode);

                User::firstOrCreate(['email' => "head.{$slug}@nimr.or.tz"], [
                    'name'         => $this->randomName(),
                    'password'     => Hash::make('password'),
                    'unit_id'      => $section->id,
                    'job_title'    => 'Head of Section',
                    'role'         => 'head',
                    'staff_number' => strtoupper($sCode) . '-HEAD-001',
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]);

                User::firstOrCreate(['email' => "staff.{$slug}@nimr.or.tz"], [
                    'name'         => $this->randomName(),
                    'password'     => Hash::make('password'),
                    'unit_id'      => $section->id,
                    'job_title'    => 'Research Officer',
                    'role'         => 'staff',
                    'staff_number' => strtoupper($sCode) . '-STF-001',
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]);
            }
        }

        // RIRAD sections
        $rirad = Unit::where('code', 'RIRAD')->first();
        if ($rirad) {
            User::firstOrCreate(['email' => 'director.rirad@nimr.or.tz'], [
                'name'         => $this->randomName(),
                'password'     => Hash::make('password'),
                'unit_id'      => $rirad->id,
                'job_title'    => 'Director of Research Information',
                'role'         => 'director',
                'staff_number' => 'RIRAD-DIR-001',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);

            foreach (['HRRS', 'DSS', 'RPDS'] as $sCode) {
                $section = Unit::where('code', $sCode)->first();
                if (!$section) continue;
                $slug = strtolower($sCode);

                User::firstOrCreate(['email' => "head.{$slug}@nimr.or.tz"], [
                    'name'         => $this->randomName(),
                    'password'     => Hash::make('password'),
                    'unit_id'      => $section->id,
                    'job_title'    => 'Head of Section',
                    'role'         => 'head',
                    'staff_number' => strtoupper($sCode) . '-HEAD-001',
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]);

                User::firstOrCreate(['email' => "staff.{$slug}@nimr.or.tz"], [
                    'name'         => $this->randomName(),
                    'password'     => Hash::make('password'),
                    'unit_id'      => $section->id,
                    'job_title'    => 'Research Officer',
                    'role'         => 'staff',
                    'staff_number' => strtoupper($sCode) . '-STF-001',
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]);
            }
        }

        // CSD — PMES section (FAS already seeded in DatabaseSeeder)
        $pmes = Unit::where('code', 'PMES')->first();
        $csd  = Unit::where('code', 'CSD')->first();
        if ($pmes && $csd) {
            User::firstOrCreate(['email' => 'head.pmes@nimr.or.tz'], [
                'name'         => $this->randomName(),
                'password'     => Hash::make('password'),
                'unit_id'      => $pmes->id,
                'job_title'    => 'Head of Planning',
                'role'         => 'head',
                'staff_number' => 'PMES-HEAD-001',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);

            User::firstOrCreate(['email' => 'staff.pmes@nimr.or.tz'], [
                'name'         => $this->randomName(),
                'password'     => Hash::make('password'),
                'unit_id'      => $pmes->id,
                'job_title'    => 'Planning Officer',
                'role'         => 'staff',
                'staff_number' => 'PMES-STF-001',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        }
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function randomName(): string
    {
        return $this->firstNames[array_rand($this->firstNames)]
            . ' '
            . $this->lastNames[array_rand($this->lastNames)];
    }

    private function approveComment(): ?string
    {
        return fake()->randomElement([
            'Reviewed and approved. Travel aligns with institutional objectives.',
            'Approved. Ensure compliance with field protocols.',
            'Approved as presented. Safe travels.',
            null,
            null,
        ]);
    }

    private function rejectComment(): string
    {
        return fake()->randomElement([
            'Budget for this quarter is exhausted. Please reapply next quarter.',
            'Purpose of travel does not align with current research priorities.',
            'Insufficient justification provided for international travel.',
            'Another staff member has already been assigned to this activity.',
        ]);
    }

    private function returnComment(): string
    {
        return fake()->randomElement([
            'Please attach an updated budget line breakdown and invitation letter.',
            'Section D requires more detail on the institutional benefit.',
            'Handover document is incomplete — please update before resubmitting.',
            'Travel dates overlap with a critical project deadline. Please revise.',
        ]);
    }
}
