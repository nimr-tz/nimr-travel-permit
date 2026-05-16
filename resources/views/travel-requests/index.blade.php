<x-app-layout>
<div class="max-w-5xl mx-auto px-6 py-6 space-y-5">

    {{-- ── Page header ──────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                {{ ($user->isHr() || $user->isDirectorGeneral()) ? __('travel.list_all') : __('travel.list_mine') }}
            </h1>
            <p class="text-sm text-slate-400 mt-0.5">
                @if ($requests->total() > 0)
                    {{ __('travel.results_count', ['from' => $requests->firstItem(), 'to' => $requests->lastItem(), 'total' => $requests->total()]) }}
                @else
                    {{ __('travel.no_results') }}
                @endif
            </p>
        </div>
        @if (!$user->isHr() && !$user->isDirectorGeneral())
        <a href="{{ route('travel-requests.create') }}" class="btn-primary shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('travel.new_request') }}
        </a>
        @endif
    </div>

    @if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
        class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="flex-1">{{ session('status') }}</span>
        <button @click="show = false"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    @endif

    {{-- ── Search bar ────────────────────────────────────────────────── --}}
    <form method="GET" action="{{ route('travel-requests.index') }}"
          class="card flex items-center overflow-hidden">
        <div class="flex-1 flex items-center gap-2 px-4 py-2.5 border-r border-slate-200">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="{{ __('travel.search_placeholder') }}"
                   class="flex-1 bg-transparent text-sm text-slate-900 placeholder-slate-400 outline-none">
            @if (request('q'))
            <a href="{{ route('travel-requests.index', array_filter(['status' => request('status')])) }}"
               class="text-slate-300 hover:text-slate-500 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
            @endif
        </div>
        <button type="submit"
                class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                style="background-color:#05499c;">
            {{ __('common.search') }}
        </button>
    </form>

    {{-- ── Status tabs ───────────────────────────────────────────────── --}}
    @php
        $totalCount = array_sum($statusCounts);
        $tabs = [
            ''           => __('travel.all_statuses'),
            'pending'    => __('common.status_pending'),
            'approved'   => __('common.status_approved'),
            'returned'   => __('common.status_returned'),
            'rejected'   => __('common.status_rejected'),
            'draft'      => __('common.status_draft'),
            'cancelled'  => __('common.status_cancelled'),
        ];
        $activeTab = request('status', '');
    @endphp
    <div class="flex items-center gap-0 border-b border-slate-200 overflow-x-auto -mb-1">
        @foreach ($tabs as $val => $label)
        @php
            $count = $val === '' ? $totalCount : ($statusCounts[$val] ?? 0);
            $isActive = $activeTab === $val;
        @endphp
        @if ($count > 0 || $isActive || $val === '')
        <a href="{{ route('travel-requests.index', array_filter(['q' => request('q'), 'status' => $val ?: null])) }}"
           class="flex items-center gap-1.5 px-3.5 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition -mb-px
                  {{ $isActive ? 'border-current' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}"
           style="{{ $isActive ? 'color:#05499c; border-color:#05499c;' : '' }}">
            {{ $label }}
            @if ($count > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold leading-none
                         {{ $isActive ? '' : 'bg-slate-100 text-slate-500' }}"
                  style="{{ $isActive ? 'background-color:#05499c18; color:#05499c;' : '' }}">{{ $count }}</span>
            @endif
        </a>
        @endif
        @endforeach
    </div>

    {{-- ── Request list ──────────────────────────────────────────────── --}}
    @if ($requests->isEmpty())
    <div class="card p-14 text-center">
        <div class="h-14 w-14 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p class="text-base font-medium text-slate-600 mb-1">{{ __('travel.no_found') }}</p>
        @if (request()->hasAny(['q', 'status']))
        <p class="text-sm text-slate-400 mb-5">{{ __('travel.adjust_filters') }}</p>
        <a href="{{ route('travel-requests.index') }}" class="btn-secondary btn-sm">{{ __('travel.reset_search') }}</a>
        @elseif (!$user->isHr() && !$user->isDirectorGeneral())
        <p class="text-sm text-slate-400 mb-5">{{ __('travel.no_submitted') }}</p>
        <a href="{{ route('travel-requests.create') }}" class="btn-primary btn-sm">{{ __('travel.first_submit') }}</a>
        @endif
    </div>
    @else
    <div class="card overflow-hidden divide-y divide-slate-100">
        @foreach ($requests as $tr)
        <a href="{{ route('travel-requests.show', $tr) }}"
           class="group flex items-start gap-4 px-5 py-4 hover:bg-slate-50/70 transition">

            {{-- Icon --}}
            @if ($user->isHr() || $user->isDirectorGeneral())
            <div class="h-10 w-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold text-slate-500 group-hover:bg-slate-200 transition">
                {{ strtoupper(substr($tr->b_applicant_name ?? '?', 0, 2)) }}
            </div>
            @else
            <div class="h-10 w-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5 transition"
                 style="background-color:#05499c12;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#05499c;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            @endif

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                {{-- Primary row --}}
                <div class="flex items-center gap-2 flex-wrap">
                    @if ($user->isHr() || $user->isDirectorGeneral())
                    <span class="text-sm font-semibold text-slate-800 group-hover:text-slate-900 transition">{{ $tr->b_applicant_name ?? '—' }}</span>
                    @else
                    <span class="text-sm font-semibold text-slate-800 group-hover:text-slate-900 transition">{{ $tr->b_destination ?? '—' }}</span>
                    @endif
                    <span class="badge {{ $tr->statusColor() }}">{{ $tr->statusLabel() }}</span>
                </div>

                {{-- Secondary info --}}
                <div class="flex items-center flex-wrap gap-x-3 gap-y-0.5 mt-1">
                    @if ($user->isHr() || $user->isDirectorGeneral())
                    {{-- HR/DG: destination · unit --}}
                    @if ($tr->b_destination)
                    <span class="text-xs text-slate-400">{{ $tr->b_destination }}</span>
                    @endif
                    @if ($tr->unit)
                    <span class="text-xs text-slate-300">&middot;</span>
                    <span class="text-xs text-slate-400">{{ $tr->unit->name }}</span>
                    @endif
                    @else
                    {{-- Staff: waiting for / dates --}}
                    @if ($tr->status === \App\Models\TravelRequest::STATUS_PENDING && $tr->currentApprover)
                    <span class="text-xs text-amber-600 font-medium">{{ __('dashboard.waiting_for', ['name' => $tr->currentApprover->name]) }}</span>
                    @endif
                    @endif

                    {{-- Departure date --}}
                    @if ($tr->b_departure_date)
                    @if (!($user->isHr() || $user->isDirectorGeneral()) && $tr->status === \App\Models\TravelRequest::STATUS_PENDING && $tr->currentApprover)
                    <span class="text-xs text-slate-300">&middot;</span>
                    @endif
                    <span class="flex items-center gap-1 text-xs text-slate-400">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $tr->b_departure_date->format('d M Y') }}
                        @if ($tr->b_return_date)
                        &mdash; {{ $tr->b_return_date->format('d M Y') }}
                        @endif
                    </span>
                    @endif
                </div>
            </div>

            {{-- Right: submitted date + chevron --}}
            <div class="text-right shrink-0 flex flex-col items-end gap-1.5 self-center">
                @if ($tr->submitted_at)
                <span class="text-xs text-slate-400">{{ $tr->submitted_at->format('d M Y') }}</span>
                @elseif ($tr->status === \App\Models\TravelRequest::STATUS_DRAFT)
                <span class="text-xs text-slate-400">{{ $tr->created_at->format('d M Y') }}</span>
                @endif
                <svg class="w-4 h-4 text-slate-300 group-hover:text-slate-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>

        </a>
        @endforeach
    </div>
    @endif

    {{-- Pagination --}}
    @if ($requests->hasPages())
    <div class="flex justify-center">
        {{ $requests->links() }}
    </div>
    @endif

</div>
</x-app-layout>
