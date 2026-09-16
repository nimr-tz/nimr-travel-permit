<x-app-layout>
@php
    $userIdKeys = \App\Http\Controllers\AuditLogController::USER_ID_KEYS;

    // Human-readable value for a logged field: names instead of ids, labels
    // instead of raw role/status codes.
    $display = function (string $key, mixed $value) use ($names, $userIdKeys): string {
        if ($value === null || $value === '') {
            return '—';
        }
        if (is_bool($value) || in_array($key, ['is_active', 'known_account', 'remember_me', 'replaced_previous', 'password_set_by_admin'], true)) {
            return $value ? __('audit.yes') : __('audit.no');
        }
        if (in_array($key, $userIdKeys, true) && is_numeric($value)) {
            return $names['users'][(int) $value] ?? '#'.$value;
        }
        if ($key === 'unit_id' && is_numeric($value)) {
            return $names['units'][(int) $value] ?? '#'.$value;
        }
        if ($key === 'role' && is_string($value)) {
            return __('common.role_'.$value);
        }
        if (in_array($key, ['status', 'resulting_status'], true) && is_string($value)) {
            return __('common.status_'.$value);
        }
        if (is_array($value)) {
            return collect($value)->map(fn ($v, $k) => (is_string($k) ? $k.': ' : '').(is_array($v) ? json_encode($v) : $v))->join(', ');
        }

        return (string) $value;
    };

    $fieldLabel = fn (string $key) => \Illuminate\Support\Facades\Lang::has('audit.field.'.$key)
        ? __('audit.field.'.$key)
        : \Illuminate\Support\Str::headline(preg_replace('/^[a-g]_/', '', $key));

    $categoryStyles = [
        'auth'           => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'dot' => '#3b82f6'],
        'account'        => ['bg' => '#f5f3ff', 'text' => '#6d28d9', 'dot' => '#8b5cf6'],
        'travel_request' => ['bg' => '#ecfdf5', 'text' => '#047857', 'dot' => '#10b981'],
        'download'       => ['bg' => '#f1f5f9', 'text' => '#475569', 'dot' => '#64748b'],
        'admin'          => ['bg' => '#fffbeb', 'text' => '#b45309', 'dot' => '#f59e0b'],
    ];
    $warningStyle = ['bg' => '#fef2f2', 'text' => '#b91c1c', 'dot' => '#ef4444'];

    $hasFilters = collect(request()->only(['q', 'category', 'event', 'unit_id', 'from', 'to']))->filter()->isNotEmpty();
@endphp

<div class="px-6 py-6 lg:px-8 lg:py-8 space-y-5">

    {{-- Page header --}}
    <div class="rounded-xl border border-indigo-200 shadow-sm px-6 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4" style="background:#eef2ff;">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl flex items-center justify-center shrink-0 border border-indigo-200" style="background:rgba(255,255,255,0.6);">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('audit.title') }}</h1>
                <p class="text-sm font-medium text-indigo-700 mt-0.5">
                    {{ $isCentreScoped ? __('audit.subtitle_centre') : __('audit.subtitle') }}
                </p>
            </div>
        </div>
        <a href="{{ route('audit-log.export', request()->query()) }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition hover:opacity-90 shrink-0"
           style="background-color:#05499c;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('audit.export') }}
        </a>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('audit.stat_today') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($stats['today']) }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ __('audit.stat_today_sub') }}</p>
        </div>
        <a href="{{ route('audit-log.index', ['category' => 'auth', 'event' => 'auth.login_failed']) }}"
           class="rounded-xl border px-5 py-4 shadow-sm transition hover:shadow {{ $stats['failed_logins'] > 0 ? 'border-red-200' : 'border-slate-200 bg-white' }}"
           style="{{ $stats['failed_logins'] > 0 ? 'background:#fef2f2;' : '' }}">
            <p class="text-[10px] font-bold uppercase tracking-widest {{ $stats['failed_logins'] > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ __('audit.stat_failed') }}</p>
            <p class="mt-1 text-3xl font-bold {{ $stats['failed_logins'] > 0 ? 'text-red-700' : 'text-slate-900' }}">{{ number_format($stats['failed_logins']) }}</p>
            <p class="text-xs mt-0.5 {{ $stats['failed_logins'] > 0 ? 'text-red-600' : 'text-slate-500' }}">{{ __('audit.stat_failed_sub') }}</p>
        </a>
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('audit.stat_total') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-700">{{ number_format($stats['total']) }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ __('audit.stat_total_sub') }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('audit-log.index') }}" class="card p-4 space-y-3">
        <div class="flex flex-col lg:flex-row gap-3">
            <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-lg px-3 py-2 flex-1 focus-within:ring-2 focus-within:ring-indigo-100 focus-within:border-indigo-400 transition">
                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('audit.search_placeholder') }}"
                       class="flex-1 bg-transparent border-0 p-0 text-sm text-slate-800 placeholder-slate-400 focus:ring-0 outline-none min-w-0">
            </div>
            <select name="category" class="rounded-lg border-slate-200 text-sm text-slate-700 lg:w-52">
                <option value="">{{ __('audit.all_categories') }}</option>
                @foreach (array_keys($events) as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ __('audit.category.'.$category) }}</option>
                @endforeach
            </select>
            {{-- A specific activity takes precedence over the category. --}}
            <select name="event" class="rounded-lg border-slate-200 text-sm text-slate-700 lg:w-64">
                <option value="">{{ __('audit.all_activities') }}</option>
                @foreach ($events as $category => $list)
                    <optgroup label="{{ __('audit.category.'.$category) }}">
                        @foreach ($list as $event)
                            <option value="{{ $event }}" @selected(request('event') === $event)>{{ __('audit.event.'.$event) }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-end gap-3">
            @unless ($isCentreScoped)
            <label class="block sm:w-56">
                <span class="block text-xs font-medium text-slate-500 mb-1">{{ __('audit.unit') }}</span>
                <select name="unit_id" class="w-full rounded-lg border-slate-200 text-sm text-slate-700">
                    <option value="">{{ __('audit.all_units') }}</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string) request('unit_id') === (string) $unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
            </label>
            @endunless
            <label class="block">
                <span class="block text-xs font-medium text-slate-500 mb-1">{{ __('audit.from') }}</span>
                <input type="date" name="from" value="{{ request('from') }}" class="w-full rounded-lg border-slate-200 text-sm text-slate-700">
            </label>
            <label class="block">
                <span class="block text-xs font-medium text-slate-500 mb-1">{{ __('audit.to') }}</span>
                <input type="date" name="to" value="{{ request('to') }}" class="w-full rounded-lg border-slate-200 text-sm text-slate-700">
            </label>
            <div class="flex items-center gap-2 sm:ml-auto">
                @if ($hasFilters)
                    <a href="{{ route('audit-log.index') }}" class="btn-ghost">{{ __('audit.clear') }}</a>
                @endif
                <button type="submit" class="btn-primary">{{ __('audit.filter') }}</button>
            </div>
        </div>
        @if ($errors->any())
            <p class="text-sm text-red-600">{{ $errors->first() }}</p>
        @endif
    </form>

    @if ($logs->total() > 0)
        <p class="text-xs text-slate-500">
            {{ __('audit.showing', ['from' => number_format($logs->firstItem()), 'to' => number_format($logs->lastItem()), 'total' => number_format($logs->total())]) }}
        </p>
    @endif

    {{-- Entries --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="table-head">
                <tr>
                    <th class="table-th w-40">{{ __('audit.col_time') }}</th>
                    <th class="table-th">{{ __('audit.col_activity') }}</th>
                    <th class="table-th">{{ __('audit.col_actor') }}</th>
                    <th class="table-th hidden md:table-cell">{{ __('audit.col_subject') }}</th>
                    <th class="table-th hidden xl:table-cell">{{ __('audit.col_ip') }}</th>
                    <th class="table-th w-20"></th>
                </tr>
            </thead>
            @forelse ($logs as $log)
            @php
                $style = $log->isWarning() ? $warningStyle : ($categoryStyles[$log->category()] ?? $categoryStyles['download']);
                $hasContext = ! empty($log->context) || $log->user_agent;
                $hasDetails = ! empty($log->changes['after']) || $hasContext;
            @endphp
            <tbody x-data="{ open: false }" class="border-b border-slate-100 last:border-b-0">
                <tr class="hover:bg-slate-50 transition align-top">
                    <td class="table-td whitespace-nowrap">
                        <div class="text-sm text-slate-800">{{ $log->performed_at->format('d M Y') }}</div>
                        <div class="text-xs text-slate-400 mt-0.5 tabular-nums">{{ $log->performed_at->format('H:i:s') }}</div>
                    </td>
                    <td class="table-td">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[12px] font-semibold"
                              style="background:{{ $style['bg'] }};color:{{ $style['text'] }};">
                            <span class="h-1.5 w-1.5 rounded-full shrink-0" style="background:{{ $style['dot'] }};"></span>
                            {{ __('audit.event.'.$log->action) }}
                        </span>
                        <div class="text-[11px] text-slate-400 mt-1">{{ __('audit.category.'.$log->category()) }}</div>
                    </td>
                    <td class="table-td">
                        @if ($log->actor_label)
                            @php [$actorName, $actorEmail] = array_pad(explode(' (', rtrim($log->actor_label, ')'), 2), 2, null); @endphp
                            <div class="text-sm font-medium text-slate-900">{{ $actorName }}</div>
                            <div class="text-xs text-slate-400 break-words">{{ $actorEmail }}</div>
                        @elseif (! empty($log->context['command']))
                            <div class="text-sm font-medium text-slate-700">{{ __('audit.system') }}</div>
                            <div class="text-xs text-slate-400">{{ __('audit.system_sub') }}</div>
                        @else
                            <div class="text-sm text-slate-500">{{ __('audit.no_actor') }}</div>
                            @if (! empty($log->context['email']))
                                <div class="text-xs text-slate-400 break-words">{{ $log->context['email'] }}</div>
                            @endif
                        @endif
                        @if ($log->unit)
                            <div class="text-[11px] text-slate-400 mt-0.5">{{ $log->unit->name }}</div>
                        @endif
                    </td>
                    <td class="table-td hidden md:table-cell">
                        @if ($log->subject_type === \App\Models\TravelRequest::class && $log->subject_id)
                            <a href="{{ route('travel-requests.show', $log->subject_id) }}" class="text-sm font-medium text-indigo-600 hover:underline">{{ $log->subject_label }}</a>
                        @else
                            <span class="text-sm text-slate-700 break-words">{{ $log->subject_label ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="table-td hidden xl:table-cell">
                        <span class="text-xs font-mono text-slate-500">{{ $log->ip_address ?? '—' }}</span>
                    </td>
                    <td class="table-td text-right">
                        @if ($hasDetails)
                        <button type="button" @click="open = !open" :aria-expanded="open"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition">
                            <span x-text="open ? @js(__('audit.hide_details')) : @js(__('audit.details'))">{{ __('audit.details') }}</span>
                            <svg class="w-3.5 h-3.5 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        @endif
                    </td>
                </tr>
                @if ($hasDetails)
                <tr x-show="open" x-cloak class="bg-slate-50/70">
                    <td colspan="6" class="px-5 pb-5 pt-1">
                        <div class="grid gap-4 lg:grid-cols-2">
                            @if (! empty($log->changes['after']))
                            <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
                                <p class="px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100">{{ __('audit.changes') }}</p>
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400">
                                            <th class="px-4 py-2 font-semibold w-1/3"></th>
                                            <th class="px-4 py-2 font-semibold">{{ __('audit.before') }}</th>
                                            <th class="px-4 py-2 font-semibold">{{ __('audit.after') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($log->changes['after'] as $key => $after)
                                        <tr>
                                            <td class="px-4 py-2 text-slate-500">{{ $fieldLabel($key) }}</td>
                                            <td class="px-4 py-2 text-red-700 break-words"><span class="line-through decoration-red-300">{{ \Illuminate\Support\Str::limit($display($key, $log->changes['before'][$key] ?? null), 200) }}</span></td>
                                            <td class="px-4 py-2 text-emerald-700 font-medium break-words">{{ \Illuminate\Support\Str::limit($display($key, $after), 200) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                            @if ($hasContext)
                            <div class="rounded-lg border border-slate-200 bg-white overflow-hidden">
                                <p class="px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100">{{ __('audit.context') }}</p>
                                <dl class="divide-y divide-slate-100 text-sm">
                                    <div class="px-4 py-2 flex gap-4 md:hidden">
                                        <dt class="text-slate-500 w-1/3 shrink-0">{{ __('audit.col_subject') }}</dt>
                                        <dd class="text-slate-800 break-words">{{ $log->subject_label ?? '—' }}</dd>
                                    </div>
                                    @foreach ($log->context ?? [] as $key => $value)
                                    <div class="px-4 py-2 flex gap-4">
                                        <dt class="text-slate-500 w-1/3 shrink-0">{{ $fieldLabel($key) }}</dt>
                                        <dd class="text-slate-800 break-words whitespace-pre-line">{{ $display($key, $value) }}</dd>
                                    </div>
                                    @endforeach
                                    <div class="px-4 py-2 flex gap-4 xl:hidden">
                                        <dt class="text-slate-500 w-1/3 shrink-0">{{ __('audit.col_ip') }}</dt>
                                        <dd class="text-slate-800 font-mono text-xs">{{ $log->ip_address ?? '—' }}</dd>
                                    </div>
                                    @if ($log->user_agent)
                                    <div class="px-4 py-2 flex gap-4">
                                        <dt class="text-slate-500 w-1/3 shrink-0">{{ __('audit.browser') }}</dt>
                                        <dd class="text-slate-600 text-xs break-all">{{ $log->user_agent }}</dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endif
            </tbody>
            @empty
            <tbody>
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <p class="text-sm text-slate-500">{{ $hasFilters ? __('audit.empty') : __('audit.empty_all') }}</p>
                            @if ($hasFilters)
                                <a href="{{ route('audit-log.index') }}" class="text-sm text-indigo-600 hover:underline">{{ __('audit.clear') }}</a>
                            @endif
                        </div>
                    </td>
                </tr>
            </tbody>
            @endforelse
        </table>
        </div>
    </div>

    @if ($logs->hasPages())
    <div class="flex justify-center">
        {{ $logs->links() }}
    </div>
    @endif

</div>
</x-app-layout>
