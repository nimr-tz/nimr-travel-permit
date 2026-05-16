<x-app-layout>

    <div class="p-6 space-y-6 max-w-6xl mx-auto">

        @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="flex-1">{{ session('status') }}</span>
            <button @click="show = false"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        @endif

        {{-- ── Welcome banner ─────────────────────────────────────────── --}}
        <div class="rounded-xl p-6 flex items-center justify-between gap-6 text-white" style="background-color:#05499c;">
            <div>
                <p class="text-blue-200 text-sm font-medium mb-1">{{ __('dashboard.welcome') }}</p>
                <h2 class="text-xl font-bold">{{ $user->name }}</h2>
                <p class="text-blue-200 text-sm mt-0.5">
                    {{ $user->job_title ?? __('common.role_' . $user->role) }}
                    @if($user->unit) &middot; {{ $user->unit->name }} @endif
                </p>
            </div>
            <div class="hidden sm:flex items-center justify-center h-14 w-14 rounded-2xl text-xl font-extrabold shrink-0" style="background-color:rgba(255,255,255,0.12);">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
        </div>

        {{-- ── KPI Stats ───────────────────────────────────────────────── --}}
        @php
            $stats = [
                ['label' => __('dashboard.total'),    'value' => $totalRequests,  'num_color' => 'text-slate-800',   'bar' => '#64748b'],
                ['label' => __('dashboard.pending'),  'value' => $pendingCount,   'num_color' => 'text-amber-600',   'bar' => '#f59e0b'],
                ['label' => __('dashboard.approved'), 'value' => $approvedCount,  'num_color' => 'text-emerald-600', 'bar' => '#10b981'],
                ['label' => __('dashboard.rejected'), 'value' => $rejectedCount,  'num_color' => 'text-red-500',     'bar' => '#ef4444'],
            ];
        @endphp
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($stats as $stat)
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="h-1" style="background-color: {{ $stat['bar'] }};"></div>
                <div class="p-5">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-3">{{ $stat['label'] }}</p>
                    <p class="text-4xl font-extrabold {{ $stat['num_color'] }}">{{ $stat['value'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ── Needs My Action ─────────────────────────────────────────── --}}
        @if (!$user->isHr() && !$user->isDirectorGeneral() && $needsMyAction->count() > 0)
        <div>
            <div class="flex items-center gap-3 mb-3">
                <h3 class="text-sm font-bold text-slate-800">{{ __('dashboard.needs_action') }}</h3>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">{{ $needsMyAction->count() }}</span>
            </div>
            <div class="card overflow-hidden divide-y divide-slate-100">
                @foreach ($needsMyAction as $tr)
                <a href="{{ route('travel-requests.show', $tr) }}"
                    class="group flex items-center gap-4 px-5 py-4 hover:bg-amber-50/60 transition">
                    <div class="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800 group-hover:text-amber-700 transition">{{ $tr->b_destination ?? '—' }}</span>
                            <span class="badge-pending">{{ __('dashboard.needs_approval') }}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">{{ $tr->b_applicant_name }}@if ($tr->b_departure_date) · {{ $tr->b_departure_date->format('d M Y') }}@endif</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-amber-400 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── Approval queue (acted-on) ──────────────────────────────── --}}
        @if (!$user->isHr() && !$user->isDirectorGeneral() && $approvalRequests->count() > 0)
        <div>
            <div class="flex items-center gap-3 mb-3">
                <h3 class="text-sm font-bold text-slate-800">{{ __('dashboard.approval_queue') }}</h3>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-600">{{ $approvalRequests->count() }}</span>
            </div>
            <div class="card overflow-hidden divide-y divide-slate-100">
                @foreach ($approvalRequests as $tr)
                <a href="{{ route('travel-requests.show', $tr) }}"
                    class="group flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition">
                    <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800">{{ $tr->b_destination ?? '—' }}</span>
                            <span class="badge {{ $tr->statusColor() }}">{{ $tr->statusLabel() }}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">{{ $tr->b_applicant_name }}@if ($tr->b_departure_date) · {{ $tr->b_departure_date->format('d M Y') }}@endif</p>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-400 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ── My Requests ─────────────────────────────────────────────── --}}
        @if (!$user->isHr() && !$user->isDirectorGeneral())
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-800">{{ __('dashboard.my_requests') }}</h3>
                @if ($myRequests->count() >= 5)
                <a href="{{ route('travel-requests.index') }}" class="text-xs font-medium transition hover:underline" style="color:#05499c;">{{ __('dashboard.view_all') }}</a>
                @endif
            </div>
            @if ($myRequests->isEmpty())
            <div class="card p-12 text-center">
                <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-sm text-slate-500 mb-4">{{ __('dashboard.no_my_requests') }}</p>
                <a href="{{ route('travel-requests.create') }}" class="btn-primary btn-sm">{{ __('dashboard.first_request') }}</a>
            </div>
            @else
            <div class="card overflow-hidden divide-y divide-slate-100">
                @foreach ($myRequests->take(5) as $tr)
                <a href="{{ route('travel-requests.show', $tr) }}"
                    class="group flex items-center gap-4 px-5 py-4 hover:bg-slate-50 transition">
                    {{-- Icon --}}
                    <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0" style="background-color:#05499c15;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#05499c;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-semibold text-slate-800 group-hover:text-slate-900">{{ $tr->b_destination ?? '—' }}</span>
                            <span class="badge {{ $tr->statusColor() }}">{{ $tr->statusLabel() }}</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            @if ($tr->status === 'pending' && $tr->currentApprover)
                                {{ __('dashboard.waiting_for', ['name' => $tr->currentApprover->name]) }}
                            @elseif ($tr->b_departure_date)
                                {{ $tr->b_departure_date->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                    {{-- Date + arrow --}}
                    <div class="text-right shrink-0 flex flex-col items-end gap-1">
                        @if ($tr->submitted_at)
                        <span class="text-xs text-slate-400">{{ $tr->submitted_at->format('d M Y') }}</span>
                        @endif
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- ── HR / DG: All Requests ───────────────────────────────────── --}}
        @if ($user->isHr() || $user->isDirectorGeneral())
        <div>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-800">{{ __('dashboard.all_recent') }}</h3>
                <a href="{{ route('travel-requests.index') }}" class="text-xs font-medium transition hover:underline" style="color:#05499c;">{{ __('dashboard.view_all') }}</a>
            </div>
            @if ($allRequests->isEmpty())
            <div class="card p-12 text-center">
                <p class="text-sm text-slate-400">{{ __('dashboard.no_requests') }}</p>
            </div>
            @else
            <div class="card overflow-hidden">
                <table class="w-full">
                    <thead class="table-head">
                        <tr>
                            <th class="table-th">{{ __('dashboard.request_number') }}</th>
                            <th class="table-th">{{ __('dashboard.applicant') }}</th>
                            <th class="table-th">{{ __('dashboard.destination') }}</th>
                            <th class="table-th hidden md:table-cell">{{ __('dashboard.unit') }}</th>
                            <th class="table-th hidden sm:table-cell">{{ __('dashboard.date') }}</th>
                            <th class="table-th">{{ __('common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($allRequests->take(10) as $tr)
                        <tr class="table-row cursor-pointer" onclick="window.location='{{ route('travel-requests.show', $tr) }}'">
                            <td class="table-td font-mono text-xs text-slate-400">{{ $tr->request_number }}</td>
                            <td class="table-td font-medium text-slate-900">{{ $tr->b_applicant_name }}</td>
                            <td class="table-td">{{ $tr->b_destination ?? '—' }}</td>
                            <td class="table-td hidden md:table-cell text-slate-500">{{ $tr->unit?->name ?? '—' }}</td>
                            <td class="table-td hidden sm:table-cell text-slate-500">{{ $tr->submitted_at ? $tr->submitted_at->format('d M Y') : '—' }}</td>
                            <td class="table-td"><span class="badge {{ $tr->statusColor() }}">{{ $tr->statusLabel() }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @endif

    </div>
</x-app-layout>
