<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // UNITS
        // ---------------------------------------------------------------

        // --- HQ Standalone Units (report directly to Director General) ---
        $dgo       = Unit::create(['name' => "Director General's Office",              'code' => 'DGO',   'type' => 'hq_standalone']);
        $audit     = Unit::create(['name' => 'Internal Audit Unit',                    'code' => 'IAU',   'type' => 'hq_standalone']);
        $legal     = Unit::create(['name' => 'Legal Services Unit',                    'code' => 'LSU',   'type' => 'hq_standalone']);
        $ict       = Unit::create(['name' => 'ICT Unit',                               'code' => 'ICT',   'type' => 'hq_standalone']);
        $proc      = Unit::create(['name' => 'Procurement Management Unit',            'code' => 'PMU',   'type' => 'hq_standalone']);
        $pr        = Unit::create(['name' => 'Public Relations and Communication Unit','code' => 'PRCU',  'type' => 'hq_standalone']);

        // --- HQ Directorates ---
        $rcpd = Unit::create(['name' => 'Research Coordination and Promotion Directorate',    'code' => 'RCPD', 'type' => 'hq_directorate']);
        $rirad= Unit::create(['name' => 'Research Information and Regulatory Affairs Directorate','code' => 'RIRAD','type' => 'hq_directorate']);
        $csd  = Unit::create(['name' => 'Corporate Services Directorate',                      'code' => 'CSD',  'type' => 'hq_directorate']);

        // --- HQ Sections under RCPD ---
        Unit::create(['name' => 'Public Health and Promotion Section',                        'code' => 'PHPS',  'type' => 'hq_section', 'parent_id' => $rcpd->id]);
        Unit::create(['name' => 'Health Systems, Policy and Translational Research Section',  'code' => 'HSPTRS','type' => 'hq_section', 'parent_id' => $rcpd->id]);
        Unit::create(['name' => 'Innovation, Commercialisation and Technology Transfer Section','code' => 'ICTTS','type' => 'hq_section', 'parent_id' => $rcpd->id]);

        // --- HQ Sections under RIRAD ---
        Unit::create(['name' => 'Health Research Regulation Section',     'code' => 'HRRS', 'type' => 'hq_section', 'parent_id' => $rirad->id]);
        Unit::create(['name' => 'Disease Surveillance Section',           'code' => 'DSS',  'type' => 'hq_section', 'parent_id' => $rirad->id]);
        Unit::create(['name' => 'Research Publication and Documentation Section','code' => 'RPDS','type' => 'hq_section','parent_id' => $rirad->id]);

        // --- HQ Sections under CSD ---
        Unit::create(['name' => 'Finance and Accounts Section',                     'code' => 'FAS',  'type' => 'hq_section', 'parent_id' => $csd->id]);
        Unit::create(['name' => 'Planning, Monitoring and Evaluation Section',      'code' => 'PMES', 'type' => 'hq_section', 'parent_id' => $csd->id]);
        $hrUnit = Unit::create(['name' => 'Human Resource Management and Administration Section','code' => 'HRMAS','type' => 'hq_section','parent_id' => $csd->id]);

        // --- Research Centres ---
        $amani    = Unit::create(['name' => 'Amani Research Centre',             'code' => 'ARC',   'type' => 'research_centre']);
        $dodoma   = Unit::create(['name' => 'Dodoma Research Centre',            'code' => 'DRC',   'type' => 'research_centre']);
        $mabibo   = Unit::create(['name' => 'Mabibo Traditional Medicine Centre','code' => 'MTMC',  'type' => 'research_centre']);
        $mbeya    = Unit::create(['name' => 'Mbeya Research Centre',             'code' => 'MBRC',  'type' => 'research_centre']);
        $muhimbili= Unit::create(['name' => 'Muhimbili Research Centre',         'code' => 'MRC',   'type' => 'research_centre']);
        $mwanza   = Unit::create(['name' => 'Mwanza Research Centre',            'code' => 'MWRC',  'type' => 'research_centre']);
        $tanga    = Unit::create(['name' => 'Tanga Research Centre',             'code' => 'TRC',   'type' => 'research_centre']);

        // ---------------------------------------------------------------
        // USERS — key system users for development/testing
        // ---------------------------------------------------------------

        // Director General
        $dg = User::create([
            'name'         => 'Director General',
            'email'        => 'dg@nimr.or.tz',
            'password'     => Hash::make('password'),
            'unit_id'      => $dgo->id,
            'job_title'    => 'Director General',
            'role'         => 'director_general',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        // HQ HR Officer
        User::create([
            'name'         => 'HQ HR Officer',
            'email'        => 'hr.hq@nimr.or.tz',
            'password'     => Hash::make('password'),
            'unit_id'      => $hrUnit->id,
            'job_title'    => 'Human Resource Officer',
            'role'         => 'hr',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        // HQ System Administrator - manages user identity and role assignment.
        User::create([
            'name'         => 'HQ System Administrator',
            'email'        => 'sysadmin.hq@nimr.or.tz',
            'password'     => Hash::make('password'),
            'unit_id'      => $dgo->id,
            'job_title'    => 'System Administrator',
            'role'         => 'system_admin',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        // One Centre Manager per research centre
        $centres = [
            [$amani,     'amani'],
            [$dodoma,    'dodoma'],
            [$mabibo,    'mabibo'],
            [$mbeya,     'mbeya'],
            [$muhimbili, 'muhimbili'],
            [$mwanza,    'mwanza'],
            [$tanga,     'tanga'],
        ];

        foreach ($centres as [$unit, $slug]) {
            // Centre Manager
            User::create([
                'name'         => $unit->name . ' Manager',
                'email'        => "cm.{$slug}@nimr.or.tz",
                'password'     => Hash::make('password'),
                'unit_id'      => $unit->id,
                'job_title'    => 'Centre Manager',
                'role'         => 'centre_manager',
                'email_verified_at' => now(),
            'is_active'         => true,
            ]);

            // HR Officer per centre
            User::create([
                'name'         => $unit->name . ' HR Officer',
                'email'        => "hr.{$slug}@nimr.or.tz",
                'password'     => Hash::make('password'),
                'unit_id'      => $unit->id,
                'job_title'    => 'HR Officer',
                'role'         => 'hr',
                'email_verified_at' => now(),
            'is_active'         => true,
            ]);

            // Centre System Administrator - can identify/manage users in this centre.
            User::create([
                'name'         => $unit->name . ' System Administrator',
                'email'        => "sysadmin.{$slug}@nimr.or.tz",
                'password'     => Hash::make('password'),
                'unit_id'      => $unit->id,
                'job_title'    => 'System Administrator',
                'role'         => 'system_admin',
                'email_verified_at' => now(),
                'is_active'         => true,
            ]);
        }

        // One sample staff member at Mwanza Research Centre (for testing)
        User::create([
            'name'         => 'Sample Staff Mwanza',
            'email'        => 'staff.mwanza@nimr.or.tz',
            'password'     => Hash::make('password'),
            'unit_id'      => $mwanza->id,
            'job_title'    => 'Research Officer',
            'role'         => 'staff',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        // Corporate Services Directorate — Director
        User::create([
            'name'         => 'Director Corporate Services',
            'email'        => 'director.csd@nimr.or.tz',
            'password'     => Hash::make('password'),
            'unit_id'      => $csd->id,
            'job_title'    => 'Director of Corporate Services',
            'role'         => 'director',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        // Finance and Accounts Section — Head + sample staff
        $fas = Unit::where('code', 'FAS')->first();

        User::create([
            'name'         => 'Head Finance and Accounts',
            'email'        => 'head.fas@nimr.or.tz',
            'password'     => Hash::make('password'),
            'unit_id'      => $fas->id,
            'job_title'    => 'Head of Finance and Accounts',
            'role'         => 'head',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);

        User::create([
            'name'         => 'Sample Staff HQ',
            'email'        => 'staff.hq@nimr.or.tz',
            'password'     => Hash::make('password'),
            'unit_id'      => $fas->id,
            'job_title'    => 'Accounts Officer',
            'role'         => 'staff',
            'email_verified_at' => now(),
            'is_active'         => true,
        ]);
    }
}
