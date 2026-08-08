<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The SQLite table rebuild in 2026_05_18_113454_fix_travel_requests_status_constraint
 * copied the data into a fresh table but not the indexes created in
 * 2026_05_15_200000, and never carried over the uniqueness of request_number.
 *
 * This restores the workflow indexes and makes duplicate permit numbers
 * impossible at the database level.
 */
return new class extends Migration
{
    private const INDEXES = [
        'idx_tr_status' => 'status',
        'idx_tr_current_approver' => 'current_approver_id',
        'idx_tr_requester' => 'requester_id',
        'idx_tr_unit' => 'unit_id',
        'idx_tr_submitted_at' => 'submitted_at',
    ];

    private const UNIQUE_INDEX = 'travel_requests_request_number_unique';

    public function up(): void
    {
        $this->deduplicateRequestNumbers();

        $existing = $this->existingIndexNames();

        Schema::table('travel_requests', function (Blueprint $table) use ($existing) {
            foreach (self::INDEXES as $name => $column) {
                if (! in_array($name, $existing, true)) {
                    $table->index($column, $name);
                }
            }

            if (! in_array(self::UNIQUE_INDEX, $existing, true)) {
                $table->unique('request_number', self::UNIQUE_INDEX);
            }
        });
    }

    public function down(): void
    {
        $existing = $this->existingIndexNames();

        Schema::table('travel_requests', function (Blueprint $table) use ($existing) {
            foreach (array_keys(self::INDEXES) as $name) {
                if (in_array($name, $existing, true)) {
                    $table->dropIndex($name);
                }
            }

            if (in_array(self::UNIQUE_INDEX, $existing, true)) {
                $table->dropUnique(self::UNIQUE_INDEX);
            }
        });
    }

    /**
     * @return list<string>
     */
    private function existingIndexNames(): array
    {
        return array_map(
            fn (array $index) => (string) $index['name'],
            Schema::getIndexes('travel_requests'),
        );
    }

    /**
     * A unique index cannot be created over existing duplicates. Keep the
     * earliest row's number and suffix any later collisions so the historical
     * record stays traceable.
     */
    private function deduplicateRequestNumbers(): void
    {
        $duplicates = DB::table('travel_requests')
            ->select('request_number')
            ->groupBy('request_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('request_number');

        foreach ($duplicates as $number) {
            $ids = DB::table('travel_requests')
                ->where('request_number', $number)
                ->orderBy('id')
                ->pluck('id')
                ->skip(1);

            foreach ($ids as $offset => $id) {
                DB::table('travel_requests')
                    ->where('id', $id)
                    ->update(['request_number' => $number . '-D' . ($offset + 1)]);
            }
        }
    }
};
