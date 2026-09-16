<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The system audit trail for administrators. Read-only: entries are written by
 * AuditLogger and can never be changed or removed from here.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $admin = $request->user();

        $logs = $this->filteredQuery($request, $admin)
            ->with('unit:id,name')
            ->paginate(50)
            ->withQueryString();

        $visible = fn () => ActivityLog::query()->visibleTo($admin);

        $stats = [
            'today'         => $visible()->where('performed_at', '>=', today())->count(),
            'failed_logins' => $visible()->whereIn('action', ['auth.login_failed', 'auth.lockout'])
                ->where('performed_at', '>=', now()->subDay())->count(),
            'total'         => $visible()->count(),
        ];

        return view('audit-log.index', [
            'logs'      => $logs,
            'stats'     => $stats,
            'events'    => ActivityLog::EVENTS,
            'units'     => $admin->isCentreSystemAdmin() ? collect() : Unit::orderBy('name')->get(['id', 'name']),
            'names'     => $this->namesReferencedBy($logs->getCollection()),
            'isCentreScoped' => $admin->isCentreSystemAdmin(),
        ]);
    }

    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        $admin = $request->user();
        $query = $this->filteredQuery($request, $admin)->with('unit:id,name');

        $audit->log('download.audit_export', context: [
            'rows'    => (clone $query)->count(),
            'filters' => array_filter($request->query()) ?: null,
        ]);

        $filename = 'audit-log-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8 names correctly

            fputcsv($out, ['Date & time', 'Category', 'Activity', 'Performed by', 'Unit', 'Record', 'Details', 'IP address', 'Browser']);

            // Newest first, walked by id so a large export never loads at once.
            $query->reorder()->lazyByIdDesc(500, 'id')->each(function (ActivityLog $log) use ($out) {
                fputcsv($out, array_map($this->csvSafe(...), [
                    $log->performed_at?->format('Y-m-d H:i:s'),
                    __('audit.category.'.$log->category(), locale: 'en'),
                    __('audit.event.'.$log->action, locale: 'en'),
                    $log->actor_label ?? __('audit.system', locale: 'en'),
                    $log->unit?->name,
                    $log->subject_label,
                    $this->detailsForCsv($log),
                    $log->ip_address,
                    $log->user_agent,
                ]));
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredQuery(Request $request, User $admin): Builder
    {
        $request->validate([
            'q'        => ['nullable', 'string', 'max:200'],
            'category' => ['nullable', 'in:'.implode(',', array_keys(ActivityLog::EVENTS))],
            'event'    => ['nullable', 'in:'.implode(',', ActivityLog::allEvents())],
            'unit_id'  => ['nullable', 'integer'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date'],
        ]);

        $query = ActivityLog::query()
            ->visibleTo($admin)
            ->orderByDesc('performed_at')
            ->orderByDesc('id');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(fn (Builder $s) => $s
                ->where('actor_label', 'like', "%{$q}%")
                ->orWhere('subject_label', 'like', "%{$q}%")
                ->orWhere('ip_address', 'like', "%{$q}%")
                ->orWhere('context', 'like', "%{$q}%"));
        }

        if ($event = $request->input('event')) {
            $query->where('action', $event);
        } elseif ($category = $request->input('category')) {
            $query->whereIn('action', ActivityLog::EVENTS[$category]);
        }

        if ($request->filled('unit_id') && ! $admin->isCentreSystemAdmin()) {
            $query->where('unit_id', $request->integer('unit_id'));
        }

        if ($from = $request->input('from')) {
            $query->where('performed_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to = $request->input('to')) {
            $query->where('performed_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return $query;
    }

    /**
     * Logs store people and units by id; show their names instead.
     *
     * @return array{users: Collection, units: Collection}
     */
    private function namesReferencedBy(Collection $logs): array
    {
        $userIds = collect();
        $unitIds = collect();

        foreach ($logs as $log) {
            foreach ([$log->context ?? [], $log->changes['before'] ?? [], $log->changes['after'] ?? []] as $values) {
                foreach ($values as $key => $value) {
                    if (! is_numeric($value)) {
                        continue;
                    }
                    if (in_array($key, self::USER_ID_KEYS, true)) {
                        $userIds->push((int) $value);
                    } elseif ($key === 'unit_id') {
                        $unitIds->push((int) $value);
                    }
                }
            }
        }

        return [
            'users' => User::whereKey($userIds->unique())->pluck('name', 'id'),
            'units' => Unit::whereKey($unitIds->unique())->pluck('name', 'id'),
        ];
    }

    public const USER_ID_KEYS = [
        'supervisor_id',
        'current_approver_id',
        'first_approver_id',
        'next_approver_id',
        'on_behalf_of',
        'g_handover_officer_id',
        'requester_id',
    ];

    private function detailsForCsv(ActivityLog $log): string
    {
        $parts = [];

        foreach ($log->context ?? [] as $key => $value) {
            $parts[] = $key.': '.$this->scalar($value);
        }

        foreach ($log->changes['after'] ?? [] as $key => $value) {
            $parts[] = $key.': '.$this->scalar($log->changes['before'][$key] ?? null).' -> '.$this->scalar($value);
        }

        return implode('; ', $parts);
    }

    private function scalar(mixed $value): string
    {
        return match (true) {
            $value === null  => '—',
            is_bool($value)  => $value ? 'yes' : 'no',
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default          => (string) $value,
        };
    }

    /**
     * Stop a value starting with =, +, - or @ from running as a formula when
     * the export is opened in a spreadsheet.
     */
    private function csvSafe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
