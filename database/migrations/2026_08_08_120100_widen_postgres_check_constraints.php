<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Completes the PostgreSQL migration path.
 *
 * The `returned` and `cancelled` statuses, and the `returned` decision, were
 * only ever widened for MySQL (2026_05_17_000001) and SQLite (2026_05_18_*).
 * On PostgreSQL the original CHECK constraints survived, so those values would
 * be rejected at write time.
 *
 * No-op on every other engine.
 */
return new class extends Migration
{
    private const CONSTRAINTS = [
        'travel_requests' => [
            'name' => 'travel_requests_status_check',
            'column' => 'status',
            'up' => ['draft', 'pending', 'approved', 'rejected', 'returned', 'cancelled'],
            'down' => ['draft', 'pending', 'approved', 'rejected'],
        ],
        'approval_actions' => [
            'name' => 'approval_actions_decision_check',
            'column' => 'decision',
            'up' => ['approved', 'rejected', 'returned'],
            'down' => ['approved', 'rejected'],
        ],
    ];

    public function up(): void
    {
        $this->apply('up');
    }

    public function down(): void
    {
        $this->apply('down');
    }

    private function apply(string $direction): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::CONSTRAINTS as $table => $constraint) {
            $values = implode("','", $constraint[$direction]);

            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint['name']}");
            DB::statement(
                "ALTER TABLE {$table} ADD CONSTRAINT {$constraint['name']} "
                . "CHECK ({$constraint['column']}::text IN ('{$values}'))"
            );
        }
    }
};
