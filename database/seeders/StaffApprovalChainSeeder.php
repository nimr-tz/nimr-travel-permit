<?php

namespace Database\Seeders;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\ApprovalChainService;
use App\Services\SupervisorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Seeds the ordinary staff tier and wires every user to a supervisor that the
 * approval chain will actually accept.
 *
 * DatabaseSeeder and TravelRequestSeeder between them create the senior roles,
 * but nobody ever gets a supervisor_id: the backfill migration runs against an
 * empty table, long before any user exists. SupervisorService requires a
 * same-unit supervisor of a specific role before a staff member may submit
 * anything, so without this step every staff account is inert.
 *
 * Three passes:
 *   1. Fill the structural holes that make a chain unbuildable (units with no
 *      supervisor-eligible role in them at all).
 *   2. Assign supervisor_id for everyone, using SupervisorService so the result
 *      matches what the Dashboard would have allowed a user to pick.
 *   3. Give the newly-unblocked users a realistic spread of travel requests.
 *
 * Idempotent: re-running adds no duplicate users and no duplicate requests.
 */
class StaffApprovalChainSeeder extends Seeder
{
    private array $firstNames = [
        'Asha', 'Grace', 'Peter', 'Mary', 'John', 'Fatuma', 'Joseph', 'Amina',
        'David', 'Rehema', 'Charles', 'Zuhura', 'Emmanuel', 'Neema', 'Hamisi',
        'Joyce', 'Hassan', 'Sophia', 'Francis', 'Salma', 'Bernard', 'Zawadi',
        'Patrick', 'Mwanaisha', 'Robert', 'Esther', 'Rashid', 'Winfrida',
        'Daniel', 'Perpetua', 'Godfrey', 'Upendo', 'Yusuph', 'Happiness',
    ];

    private array $lastNames = [
        'Mwangaza', 'Kileo', 'Mkapa', 'Sengo', 'Mwaikambo', 'Maganga',
        'Chombo', 'Mwenda', 'Kisanga', 'Mapesa', 'Shayo', 'Mhando',
        'Kyaruzi', 'Mzinga', 'Tarimo', 'Mramba', 'Mwakyusa', 'Kaunda',
        'Sumari', 'Mwasumbi', 'Lema', 'Ngowi', 'Massawe', 'Kimaro',
    ];

    private array $destinations = [
        'Dar es Salaam', 'Arusha', 'Mwanza', 'Dodoma', 'Mbeya', 'Tanga',
        'Morogoro', 'Tabora', 'Iringa', 'Kigoma', 'Lindi', 'Moshi',
        'Shinyanga', 'Singida', 'Songea', 'Musoma', 'Bukoba', 'Sumbawanga',
        'Nairobi, Kenya', 'Kampala, Uganda', 'Kigali, Rwanda',
        'Geneva, Switzerland', 'Johannesburg, South Africa',
    ];

    private array $purposes = [
        'Field supervision of TB prevalence sub-study sites in the district.',
        'Attend national workshop on research data management and biostatistics.',
        'Quarterly monitoring visit to community health intervention project sites.',
        'Participate in East African regional health systems conference.',
        'Community sensitisation meetings ahead of malaria vector survey rollout.',
        'Collect and transport laboratory specimens to the reference laboratory.',
        'Routine supportive supervision of data collectors in the study districts.',
        'Present study findings at the annual joint scientific conference.',
        'Training of enumerators for the household health expenditure survey.',
        'Stakeholder engagement on translation of research findings into policy.',
    ];

    private array $budgetLines = [
        'Research & Development — Field Operations',
        'Research & Development — Training and Capacity Building',
        'Research & Development — Conferences and Dissemination',
        'Operational — Staff Development',
        'Grant-funded — NIMR-MoH Joint Programme',
    ];

    private int $seq = 0;

    public function run(): void
    {
        $this->guardAgainstProduction();

        $this->fillStructuralGaps();
        $this->assignSupervisors();
        $this->seedRequestsForIdleUsers();
    }

    // ------------------------------------------------------------------
    // Pass 1 — units that cannot produce a valid chain at all
    // ------------------------------------------------------------------

    /**
     * SupervisorService::supervisorRoleFor() demands a same-unit supervisor of a
     * specific role. Where that role is absent from a unit, every ordinary user
     * in it is permanently unable to submit, so create the missing post.
     */
    private function fillStructuralGaps(): void
    {
        // Research centres: staff/hr/system_admin need a 'supervisor' in the centre.
        // The post used to be called 'manager', which was too easily confused
        // with an hq_standalone unit's Manager; the organogram never called it
        // that. A supervisor's own supervisor is fixed to the Centre Manager,
        // so a second one is not needed to break a cycle -- it just gives staff
        // a real choice in the supervisor dropdown.
        //
        // The addresses keep their "mgr." prefix on purpose: these accounts
        // already exist from earlier runs, ensureUser() matches on email, and
        // renaming them here would seed a duplicate post beside each one.
        foreach (Unit::where('type', 'research_centre')->orderBy('name')->get() as $centre) {
            $slug = $this->slugFor($centre);

            $this->ensureUser("mgr.{$slug}@nimr.or.tz", $centre, 'supervisor', 'Research Supervisor');
            $this->ensureUser("mgr2.{$slug}@nimr.or.tz", $centre, 'supervisor', 'Laboratory Services Supervisor');

            // Ordinary staff -- the tier the demo was missing.
            $this->ensureUser("officer1.{$slug}@nimr.or.tz", $centre, 'staff', 'Research Officer');
            $this->ensureUser("officer2.{$slug}@nimr.or.tz", $centre, 'staff', 'Laboratory Technologist');
            $this->ensureUser("officer3.{$slug}@nimr.or.tz", $centre, 'staff', 'Data Officer');
        }

        // HQ sections: staff need a section lead. Scientific sections (under RCPD
        // and RIRAD) are led by a 'head', Corporate Services sections (under CSD)
        // by a 'manager' -- SupervisorService::supervisorRolesFor() accepts either,
        // but seed the one the organogram actually gives that section rather than
        // a blanket 'head'.
        foreach (Unit::where('type', 'hq_section')->with('parent')->orderBy('name')->get() as $section) {
            $slug = strtolower($section->code);

            $administrative = $section->parent?->code === 'CSD';
            $leadRole = $administrative ? 'manager' : 'head';

            if (! $this->unitHasRole($section, 'head') && ! $this->unitHasRole($section, 'manager')) {
                $this->ensureUser(
                    ($administrative ? 'mgr' : 'head') . ".{$slug}@nimr.or.tz",
                    $section,
                    $leadRole,
                    $administrative ? 'Section Manager' : 'Head of Section'
                );
            }

            $this->ensureUser("officer1.{$slug}@nimr.or.tz", $section, 'staff', 'Research Officer');
            $this->ensureUser("officer2.{$slug}@nimr.or.tz", $section, 'staff', 'Programme Officer');
        }

        // HQ standalone units: staff need a 'manager'. Several units are empty.
        foreach (Unit::where('type', 'hq_standalone')->orderBy('name')->get() as $unit) {
            if ($unit->code === 'DGO') {
                continue; // Director General's Office -- no ordinary staff tier.
            }

            $slug = strtolower($unit->code);

            if (! $this->unitHasRole($unit, 'manager')) {
                $this->ensureUser("mgr.{$slug}@nimr.or.tz", $unit, 'manager', 'Unit Manager');
            }

            $this->ensureUser("officer1.{$slug}@nimr.or.tz", $unit, 'staff', 'Officer');
            $this->ensureUser("officer2.{$slug}@nimr.or.tz", $unit, 'staff', 'Assistant Officer');
        }
    }

    // ------------------------------------------------------------------
    // Pass 2 — supervisor wiring
    // ------------------------------------------------------------------

    private function assignSupervisors(): void
    {
        $supervisors = new SupervisorService();

        $assigned = 0;
        $fixed    = 0;
        $stuck    = [];

        foreach (User::with('unit')->orderBy('id')->get() as $user) {
            if (! $user->unit) {
                continue;
            }

            // Roles that report straight to the DG (centre managers, standalone
            // unit managers, directorate directors) get a fixed supervisor.
            if ($supervisors->fixedSupervisorFor($user)) {
                if ($supervisors->applyFixedSupervisor($user)) {
                    $fixed++;
                }
                continue;
            }

            if (! $supervisors->isRequiredFor($user)) {
                continue; // e.g. a section head, who routes director -> DG
            }

            if ($user->supervisor_id
                && $supervisors->isValidCandidate((int) $user->supervisor_id, $user->unit, $user->role, $user->id)) {
                continue; // already wired correctly
            }

            $candidate = $supervisors->candidatesFor($user)->first();

            if (! $candidate) {
                $stuck[] = "{$user->email} ({$user->role} in {$user->unit->code})";
                continue;
            }

            $user->forceFill(['supervisor_id' => $candidate->id])->save();
            $assigned++;
        }

        $this->command->info("Supervisors: {$assigned} assigned, {$fixed} pinned to the Director General.");

        foreach ($stuck as $who) {
            $this->command->warn("  No eligible supervisor for {$who}");
        }
    }

    // ------------------------------------------------------------------
    // Pass 3 — travel requests for whoever still has none
    // ------------------------------------------------------------------

    private function seedRequestsForIdleUsers(): void
    {
        $chainService = new ApprovalChainService();
        $this->seq    = (int) (TravelRequest::max('id') ?? 0);

        $candidates = User::where('is_active', true)
            ->whereNotIn('role', ['director_general'])
            ->whereDoesntHave('travelRequests')
            ->with('unit')
            ->orderBy('id')
            ->get();

        $created = 0;
        $users   = 0;
        $skipped = 0;

        foreach ($candidates as $user) {
            try {
                $chain = $chainService->buildChain($user);
            } catch (RuntimeException $e) {
                $skipped++;
                continue;
            }

            $users++;

            foreach (range(1, rand(2, 5)) as $ignored) {
                $this->createRequest($user, $chain);
                $created++;
            }
        }

        $this->command->info("Requests: {$created} seeded for {$users} previously-idle users ({$skipped} still unable to submit).");
    }

    private function createRequest(User $user, array $chain): void
    {
        $this->seq++;

        $roll = rand(1, 100);
        $status = match (true) {
            $roll <= 15 => TravelRequest::STATUS_DRAFT,
            $roll <= 55 => TravelRequest::STATUS_PENDING,
            $roll <= 80 => TravelRequest::STATUS_APPROVED,
            $roll <= 90 => TravelRequest::STATUS_RETURNED,
            $roll <= 96 => TravelRequest::STATUS_REJECTED,
            default     => TravelRequest::STATUS_CANCELLED,
        };

        $depDays = match ($status) {
            TravelRequest::STATUS_APPROVED  => rand(-90, -10),
            TravelRequest::STATUS_REJECTED  => rand(-120, -20),
            TravelRequest::STATUS_CANCELLED => rand(-30, 30),
            TravelRequest::STATUS_RETURNED  => rand(-20, 20),
            TravelRequest::STATUS_PENDING   => rand(-10, 35),
            default                         => rand(7, 60),
        };

        $departure   = now()->addDays($depDays)->startOfDay();
        $returnDate  = $departure->copy()->addDays(rand(3, 10));
        $destination = $this->destinations[array_rand($this->destinations)];
        $purpose     = $this->purposes[array_rand($this->purposes)];
        $budgetLine  = $this->budgetLines[array_rand($this->budgetLines)];
        $tripDays    = max(1, (int) $departure->diffInDays($returnDate));

        $currentApproverId = null;
        $submittedAt       = null;
        $approvalChain     = $chain;
        $pendingStepIdx    = null;

        switch ($status) {
            case TravelRequest::STATUS_PENDING:
                $pendingStepIdx    = rand(0, count($chain) - 1);
                $currentApproverId = $chain[$pendingStepIdx]['approver_id'];
                $submittedAt       = now()->subDays(rand(1, 21));
                break;

            case TravelRequest::STATUS_APPROVED:
            case TravelRequest::STATUS_REJECTED:
                $submittedAt = now()->subDays(rand(14, 120));
                break;

            case TravelRequest::STATUS_RETURNED:
                $submittedAt = null;
                break;

            case TravelRequest::STATUS_DRAFT:
            case TravelRequest::STATUS_CANCELLED:
                $approvalChain = null;
                break;
        }

        $tr = TravelRequest::create([
            'request_number'      => 'NIMR-ITP-' . now()->year . '-' . str_pad((string) $this->seq, 4, '0', STR_PAD_LEFT),
            'requester_id'        => $user->id,
            'unit_id'             => $user->unit_id,
            'status'              => $status,
            'current_approver_id' => $currentApproverId,
            'approval_chain'      => $approvalChain,
            'submitted_at'        => $submittedAt,

            'b_applicant_name' => $user->name,
            'b_phone'          => '+255 7' . rand(10, 99) . ' ' . rand(100, 999) . ' ' . rand(100, 999),
            'b_email'          => $user->email,
            'b_position'       => $user->job_title ?? 'Research Officer',
            'b_destination'    => $destination,
            'b_departure_date' => $departure->toDateString(),
            'b_return_date'    => $returnDate->toDateString(),

            'c_travel_source' => $purpose,

            'd_benefit_to_institution'   => $purpose,
            'd_benefit_to_nation'        => 'Supports the national health research agenda and strengthens public health capacity.',
            'd_consequences_if_rejected' => 'Delays in project milestones and potential loss of collaborative research funding.',

            'e_transport_costs' => number_format(rand(100, 600) * 1000) . '/=',
            'e_allowance_a'     => number_format(rand(50, 80) * 1000) . '/= per day × ' . $tripDays . ' days',
            'e_budget_line'     => $budgetLine,
            'e_govt_cost_i'     => number_format(rand(100, 400) * 1000) . '/=',
            'e_govt_cost_ii'    => number_format(rand(50, 80) * 1000 * $tripDays) . '/=',

            'f_previous_travel_impact' => 'Previous field work contributed to a collaborative research protocol currently under review by the IRB.',
            'f_traveller_signed_date'  => ($submittedAt ?? now())->toDateString(),

            'g_handover_officer_name'  => $this->randomName(),
            'g_handover_officer_title' => 'Senior Research Officer',
            'g_handover_document'      => 'Detailed handover notes prepared and submitted to the section head prior to departure.',
        ]);

        $this->seedActions($tr, $chain, $status, $submittedAt, $pendingStepIdx);
    }

    private function seedActions(TravelRequest $tr, array $chain, string $status, ?\Carbon\Carbon $submittedAt, ?int $pendingStepIdx): void
    {
        if (in_array($status, [TravelRequest::STATUS_DRAFT, TravelRequest::STATUS_CANCELLED], true)) {
            return;
        }

        $actionDate = ($submittedAt ?? now()->subDays(30))->copy();

        if ($status === TravelRequest::STATUS_PENDING) {
            for ($i = 0; $i < ($pendingStepIdx ?? 0); $i++) {
                $actionDate->addDays(rand(1, 3));
                $this->recordAction($tr, $chain[$i], 'approved', $this->approveComment(), $actionDate);
            }
            return;
        }

        if ($status === TravelRequest::STATUS_APPROVED) {
            foreach ($chain as $step) {
                $actionDate->addDays(rand(1, 4));
                $this->recordAction($tr, $step, 'approved', $this->approveComment(), $actionDate);
            }
            return;
        }

        if ($status === TravelRequest::STATUS_REJECTED) {
            $rejectAt = rand(0, count($chain) - 1);

            foreach ($chain as $idx => $step) {
                $actionDate->addDays(rand(1, 3));

                if ($idx < $rejectAt) {
                    $this->recordAction($tr, $step, 'approved', $this->approveComment(), $actionDate);
                    continue;
                }

                $this->recordAction($tr, $step, 'rejected', $this->rejectComment(), $actionDate);
                break;
            }
            return;
        }

        if ($status === TravelRequest::STATUS_RETURNED) {
            $actionDate->addDays(rand(1, 5));
            $this->recordAction($tr, $chain[0], 'returned', $this->returnComment(), $actionDate);
        }
    }

    private function recordAction(TravelRequest $tr, array $step, string $decision, ?string $comment, \Carbon\Carbon $at): void
    {
        ApprovalAction::create([
            'travel_request_id' => $tr->id,
            'actor_id'          => $step['approver_id'],
            'stage'             => $step['stage'],
            'decision'          => $decision,
            'comment'           => $comment,
            'acted_at'          => $at->copy(),
        ]);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function ensureUser(string $email, Unit $unit, string $role, string $jobTitle): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $this->randomName(),
                'password'          => Hash::make('password'),
                'unit_id'           => $unit->id,
                'job_title'         => $jobTitle,
                'role'              => $role,
                'email_verified_at' => now(),
                'is_active'         => true,
            ]
        );
    }

    private function unitHasRole(Unit $unit, string $role): bool
    {
        return User::where('unit_id', $unit->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * DatabaseSeeder addresses centres by place name (cm.mwanza@), not by unit
     * code (MWRC), so mirror that convention for anything new.
     */
    private function slugFor(Unit $unit): string
    {
        return match ($unit->code) {
            'ARC'  => 'amani',
            'DRC'  => 'dodoma',
            'MTMC' => 'mabibo',
            'MBRC' => 'mbeya',
            'MRC'  => 'muhimbili',
            'MWRC' => 'mwanza',
            'TRC'  => 'tanga',
            default => strtolower($unit->code),
        };
    }

    private function randomName(): string
    {
        return $this->firstNames[array_rand($this->firstNames)]
            . ' '
            . $this->lastNames[array_rand($this->lastNames)];
    }

    private function approveComment(): ?string
    {
        $options = [
            'Reviewed and approved. Travel aligns with institutional objectives.',
            'Approved. Ensure compliance with field protocols.',
            'Approved as presented. Safe travels.',
            null,
            null,
        ];

        return $options[array_rand($options)];
    }

    private function rejectComment(): string
    {
        $options = [
            'Budget for this quarter is exhausted. Please reapply next quarter.',
            'Purpose of travel does not align with current research priorities.',
            'Insufficient justification provided for international travel.',
            'Another staff member has already been assigned to this activity.',
        ];

        return $options[array_rand($options)];
    }

    private function returnComment(): string
    {
        $options = [
            'Please attach an updated budget line breakdown and invitation letter.',
            'Section D requires more detail on the institutional benefit.',
            'Handover document is incomplete — please update before resubmitting.',
            'Travel dates overlap with a critical project deadline. Please revise.',
        ];

        return $options[array_rand($options)];
    }

    /**
     * Creates active accounts with the password "password", exactly as the
     * other seeders do. Never let that near a real deployment.
     */
    private function guardAgainstProduction(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        throw new RuntimeException(
            'StaffApprovalChainSeeder creates demo accounts with well-known passwords and must never run in production.'
        );
    }
}
