<x-app-layout>
<div class="max-w-7xl mx-auto px-6 py-6" x-data="approvalModal()">

    {{-- Flash --}}
    @if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="flex items-center gap-3 p-4 mb-5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
        <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="flex-1">{{ session('status') }}</span>
        <button @click="show = false"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    @endif

    {{-- ══ PREMIUM PAGE HEADER ════════════════════════════════════════════ --}}
    @php $tr = $travelRequest; @endphp
    <div class="rounded-xl overflow-hidden mb-6 shadow-sm" style="background-color:#05499c;">
        <div class="px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-3 flex-wrap mb-2">
                        <h1 class="text-2xl font-bold text-white tracking-tight">
                            {{ $tr->b_destination ?? __('travel.untitled') }}
                        </h1>
                        <span class="badge {{ $tr->statusColor() }} text-sm px-3 py-1">{{ $tr->statusLabel() }}</span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap text-sm mt-1" style="color:rgba(255,255,255,0.7);">
                        @if ($tr->b_applicant_name)
                        <span class="font-semibold text-white">{{ $tr->b_applicant_name }}</span>
                        @endif
                        @if ($tr->b_departure_date)
                        <span style="color:rgba(255,255,255,0.35);">&middot;</span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:rgba(255,255,255,0.5);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $tr->b_departure_date->format('d M Y') }}
                            @if ($tr->b_return_date) &mdash; {{ $tr->b_return_date->format('d M Y') }} @endif
                        </span>
                        @endif
                        @if ($tr->submitted_at)
                        <span style="color:rgba(255,255,255,0.35);">&middot;</span>
                        <span>{{ __('travel.submitted_at', ['date' => $tr->submitted_at->format('d M Y, H:i')]) }}</span>
                        @endif
                    </div>
                    @if ($tr->status === \App\Models\TravelRequest::STATUS_PENDING && $tr->currentApprover)
                    <p class="text-xs font-medium mt-2 flex items-center gap-1.5" style="color:#fcd34d;">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('travel.waiting_for', ['name' => $tr->currentApprover->name]) }}
                    </p>
                    @endif
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center gap-2 shrink-0 flex-wrap justify-end">
                    @if ($tr->isEditable() && $tr->requester_id === auth()->id())
                    <a href="{{ route('travel-requests.edit', $tr) }}"
                       class="btn btn-sm bg-white font-semibold transition hover:bg-slate-100"
                       style="color:#05499c;">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        {{ __('common.edit') }}
                    </a>
                    @endif
                    @if ($tr->isCancellable() && $tr->requester_id === auth()->id())
                    <button @click="confirmCancel()"
                            class="btn btn-sm bg-red-500 hover:bg-red-400 text-white font-semibold transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('travel.cancel_request') }}
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══ TWO-COLUMN LAYOUT ══════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- ── LEFT (2/3): approval action + official form ──────────────── --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Approval action form --}}
            @if ($tr->status === \App\Models\TravelRequest::STATUS_PENDING && (int)$tr->current_approver_id === (int)auth()->id())
            <div class="card overflow-hidden" style="border-left: 4px solid #05499c;">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3" style="background-color:#05499c0a;">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center shrink-0" style="background-color:#05499c20;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#05499c;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">{{ __('travel.approval_action') }}</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ __('travel.read_before_decide') }}</p>
                    </div>
                </div>
                <form id="approval-form" method="POST" action="{{ route('travel-requests.approve', $tr) }}" class="p-5 space-y-4">
                    @csrf
                    <input type="hidden" name="decision" x-model="selectedDecision">

                    @if ($errors->any())
                    <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-xs">
                        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                    @endif

                    <div class="field">
                        <label class="label">
                            {{ __('travel.comments') }}
                            <span class="font-normal text-slate-400">{{ __('travel.comments_hint') }}</span>
                        </label>
                        <textarea name="comment" rows="3" placeholder="{{ __('travel.comments') }}..." class="input resize-none">{{ old('comment') }}</textarea>
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <button type="button" @click="openModal('approved')" class="btn-success btn-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            {{ __('travel.approve_btn') }}
                        </button>
                        <button type="button" @click="openModal('returned')" class="btn-warning btn-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            {{ __('travel.return_btn') }}
                        </button>
                        <button type="button" @click="openModal('rejected')" class="btn-danger btn-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            {{ __('travel.reject_btn') }}
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- Official Form --}}
            <div class="card overflow-hidden">
                <div class="text-center px-8 py-6 border-b-2 border-slate-800 bg-white">
                    <p class="text-right text-xs italic font-semibold text-slate-600">{{ __('travel.form_number') }}</p>
                    <div class="flex justify-center my-3">
                        <img src="{{ asset('NIMR.png') }}" alt="NIMR" class="h-14 w-14 object-contain">
                    </div>
                    <h1 class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ __('travel.institution_name') }}</h1>
                    <h2 class="text-xs font-bold uppercase tracking-wide mt-1 text-slate-800">{{ __('travel.form_full_name') }}</h2>
                </div>

                @php
                    $ro     = 'flex-1 text-sm text-slate-800 border-b border-slate-200 py-0.5 px-1 bg-transparent';
                    $numCls = 'text-sm w-10 shrink-0 text-slate-400';
                    $lblCls = 'text-sm w-72 shrink-0 text-slate-600';
                @endphp

                <div class="p-6 space-y-8">

                    {{-- A --}}
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-2 mb-4">A: {{ __('travel.section_a_title') }}</h3>
                        <div class="text-xs space-y-2 text-slate-600 leading-relaxed bg-slate-50 rounded-lg p-4 border border-slate-100">
                            <p><span class="font-semibold text-slate-700">(i)</span> {{ __('travel.section_a_i') }}</p>
                            <p><span class="font-semibold text-slate-700">(ii)</span> {{ __('travel.section_a_ii') }}</p>
                            <p><span class="font-semibold text-slate-700">(iii)</span> {{ __('travel.section_a_iii') }}</p>
                            <p><span class="font-semibold text-slate-700">(iv)</span> {{ __('travel.section_a_iv') }}</p>
                            <p><span class="font-semibold text-slate-700">(v)</span> {{ __('travel.section_a_v') }}</p>
                            <p><span class="font-semibold text-slate-700">(vi)</span> {{ __('travel.section_a_viii') }}</p>
                        </div>
                    </div>

                    {{-- B --}}
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-2 mb-4">B: {{ __('travel.section_b_title') }}</h3>
                        <div class="space-y-3">
                            @foreach ([
                                ['(i)',   __('travel.b_name'),           $tr->b_applicant_name],
                                ['(ii)',  __('travel.b_phone'),          $tr->b_phone],
                                ['(iii)', __('travel.b_email'),          $tr->b_email],
                                ['(iv)',  __('travel.b_position'),       $tr->b_position],
                                ['(v)',   __('travel.b_destination'),    $tr->b_destination],
                                ['(vi)',  __('travel.b_departure_date'), $tr->b_departure_date?->format('d M Y')],
                                ['(vii)', __('travel.b_return_date'),    $tr->b_return_date?->format('d M Y')],
                            ] as [$num, $label, $value])
                            <div class="flex items-baseline gap-2">
                                <span class="{{ $numCls }}">{{ $num }}</span>
                                <span class="{{ $lblCls }}">{{ $label }}</span>
                                <span class="{{ $ro }}">{{ $value ?? '—' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- C --}}
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-2 mb-3">C: {{ __('travel.section_c_title') }}</h3>
                        <div class="text-sm text-slate-800 bg-slate-50 rounded-lg p-4 border border-slate-100 whitespace-pre-wrap min-h-[4rem]">{{ $tr->c_travel_source ?? '—' }}</div>
                    </div>

                    {{-- D --}}
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-2 mb-4">D: {{ __('travel.section_d_title') }}</h3>
                        <p class="text-sm font-medium text-slate-600 mb-3">{{ __('travel.d_benefits_sub') }}</p>
                        <div class="space-y-4">
                            @foreach ([
                                [__('travel.d_institution'), $tr->d_benefit_to_institution],
                                [__('travel.d_nation'),      $tr->d_benefit_to_nation],
                                [__('travel.d_consequences'),$tr->d_consequences_if_rejected],
                            ] as [$label, $value])
                            <div>
                                <p class="text-sm font-medium text-slate-600 mb-1.5">{{ $label }}</p>
                                <div class="text-sm text-slate-800 bg-slate-50 rounded-lg p-4 border border-slate-100 whitespace-pre-wrap min-h-[3rem]">{{ $value ?? '—' }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- E --}}
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-2 mb-4">E: {{ __('travel.section_e_title') }}</h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-slate-600 mb-1.5">{{ __('travel.e_transport') }}</p>
                                <div class="text-sm text-slate-800 bg-slate-50 rounded-lg p-4 border border-slate-100 whitespace-pre-wrap min-h-[2.5rem]">{{ $tr->e_transport_costs ?? '—' }}</div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-600 mb-2">{{ __('travel.e_allowances') }}</p>
                                <div class="ml-4 space-y-1.5">
                                    @foreach (['a' => $tr->e_allowance_a, 'b' => $tr->e_allowance_b, 'c' => $tr->e_allowance_c, 'd' => $tr->e_allowance_d] as $letter => $val)
                                    <div class="flex items-baseline gap-2">
                                        <span class="{{ $numCls }}">({{ $letter }})</span>
                                        <span class="{{ $ro }}">{{ $val ?? '—' }}</span>
                                    </div>
                                    @endforeach
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-sm shrink-0 text-slate-600">{{ __('travel.e_budget_line') }}</span>
                                        <span class="{{ $ro }}">{{ $tr->e_budget_line ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-600 mb-2">{{ __('travel.e_donor') }}</p>
                                <div class="ml-4 space-y-1">
                                    @foreach (['i' => $tr->e_donor_cost_i, 'ii' => $tr->e_donor_cost_ii, 'iii' => $tr->e_donor_cost_iii] as $num => $val)
                                    <div class="flex items-baseline gap-2"><span class="{{ $numCls }}">({{ $num }})</span><span class="{{ $ro }}">{{ $val ?? '—' }}</span></div>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-600 mb-2">{{ __('travel.e_govt') }}</p>
                                <div class="ml-4 space-y-1">
                                    @foreach (['i' => $tr->e_govt_cost_i, 'ii' => $tr->e_govt_cost_ii, 'iii' => $tr->e_govt_cost_iii] as $num => $val)
                                    <div class="flex items-baseline gap-2"><span class="{{ $numCls }}">({{ $num }})</span><span class="{{ $ro }}">{{ $val ?? '—' }}</span></div>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-600 mb-1.5">{{ __('travel.e_other') }}</p>
                                <div class="text-sm text-slate-800 bg-slate-50 rounded-lg p-4 border border-slate-100 whitespace-pre-wrap min-h-[2.5rem]">{{ $tr->e_other_costs ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- F --}}
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-2 mb-3">F: {{ __('travel.section_f_title') }}</h3>
                        <div class="text-sm text-slate-800 bg-slate-50 rounded-lg p-4 border border-slate-100 whitespace-pre-wrap min-h-[4rem]">{{ $tr->f_previous_travel_impact ?? '—' }}</div>
                    </div>

                    {{-- G --}}
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 border-b border-slate-200 pb-2 mb-3">G: {{ __('travel.section_g_title') }}</h3>
                        <div class="space-y-2 mb-3">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm w-16 shrink-0 text-slate-600">{{ __('travel.signed_name') }}</span>
                                <span class="{{ $ro }}">{{ $tr->g_handover_officer_name ?? '—' }}</span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm w-16 shrink-0 text-slate-600">{{ __('travel.signed_title') }}</span>
                                <span class="{{ $ro }}">{{ $tr->g_handover_officer_title ?? '—' }}</span>
                            </div>
                        </div>
                        @if ($tr->g_handover_document)
                        <a href="{{ route('travel-requests.download', $tr) }}" class="btn-secondary btn-sm w-fit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            {{ __('common.download') }} Handover Note
                        </a>
                        @endif
                    </div>

                    {{-- H/I/J/K approval signatures --}}
                    @if ($travelRequest->approvalActions->count() > 0)
                    <div class="border-t-2 border-slate-800 pt-6 space-y-6">
                        @php
                            $sectionLabels = [
                                'supervisor' => __('travel.h_section'),
                                'director'   => __('travel.i_section'),
                                'final'      => __('travel.j_section'),
                                'hr'         => __('travel.k_section'),
                            ];
                        @endphp
                        @foreach ($travelRequest->approvalActions as $action)
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500 border-b border-slate-200 pb-2 mb-3">
                                {{ $sectionLabels[$action->stage] ?? $action->stage }}
                            </h3>
                            @if ($action->comment)
                        <div class="mb-4">
                            <p class="text-sm text-slate-600 mb-1.5">{{ __('travel.i_benefit_label') }}</p>
                            <div class="text-sm text-slate-800 bg-slate-50 rounded-lg p-4 border border-slate-100 whitespace-pre-wrap">{{ $action->comment }}</div>
                        </div>
                        @endif
                        <div class="flex items-baseline gap-8 text-sm text-slate-700 mt-1">
                            <div>
                                <span class="text-xs text-slate-400 block mb-0.5">{{ __('travel.signed_name') }}</span>
                                <span class="font-semibold text-slate-800">{{ $action->actor?->name ?? '—' }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 block mb-0.5">{{ __('travel.signed_title') }}</span>
                                <span class="font-semibold text-slate-800">{{ $action->actor?->job_title ?? '—' }}</span>
                            </div>
                        </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                </div>
            </div>

        </div>

        {{-- ── RIGHT (1/3): approval timeline + info card ─────────────────── --}}
        <div class="space-y-5">

            {{-- Approval flow timeline — colored header --}}
            @if ($travelRequest->approval_chain)
            <div class="rounded-xl overflow-hidden shadow-sm p-5" style="background-color:#059669;">
                <h3 class="text-[10px] font-bold uppercase tracking-widest mb-5" style="color:rgba(255,255,255,0.65);">{{ __('travel.approval_flow') }}</h3>
                <div>
                    <ol class="relative ml-3 space-y-6" style="border-left: 2px solid rgba(255,255,255,0.25);">
                        @foreach ($travelRequest->approval_chain as $step)
                        @php
                            $approver  = $chainApprovers->get((int)$step['approver_id']);
                            $action    = $travelRequest->approvalActions->where('stage', $step['stage'])->first();
                            $isCurrent = (int)$travelRequest->current_approver_id === (int)$step['approver_id']
                                         && $travelRequest->status === \App\Models\TravelRequest::STATUS_PENDING;
                            $isDone    = $action !== null;

                            if ($isDone) {
                                $dotBg    = $action->decision === 'approved' ? '#10b981' : ($action->decision === 'returned' ? '#f97316' : '#ef4444');
                                $ringCls  = $action->decision === 'approved' ? 'ring-emerald-100' : ($action->decision === 'returned' ? 'ring-orange-100' : 'ring-red-100');
                                $labelCls = $action->decision === 'approved' ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : ($action->decision === 'returned' ? 'text-orange-700 bg-orange-50 border-orange-200' : 'text-red-700 bg-red-50 border-red-200');
                                $decisionLabel = __('travel.decided_' . $action->decision);
                            } elseif ($isCurrent) {
                                $dotBg    = '#f59e0b';
                                $ringCls  = 'ring-amber-100';
                                $labelCls = 'text-amber-700 bg-amber-50 border-amber-200';
                                $decisionLabel = __('travel.waiting');
                            } else {
                                $dotBg    = '#cbd5e1';
                                $ringCls  = 'ring-slate-100';
                                $labelCls = '';
                                $decisionLabel = '';
                            }
                        @endphp
                        <li class="ml-6">
                            <span class="absolute -left-[9px] w-4 h-4 rounded-full ring-4 {{ $ringCls }} {{ $isCurrent ? 'animate-pulse' : '' }}"
                                  style="background-color:{{ $dotBg }};"></span>
                            <div class="pl-1">
                                <p class="text-xs font-bold text-white">{{ __('common.stage_' . $step['stage']) }}</p>
                                <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.75);">{{ $approver?->name ?? '—' }}</p>
                                @if ($approver?->job_title)
                                <p class="text-[10px]" style="color:rgba(255,255,255,0.5);">{{ $approver->job_title }}</p>
                                @endif
                                @if ($decisionLabel)
                                <span class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $labelCls }}">
                                    {{ $decisionLabel }}
                                    @if ($isDone) &nbsp;{{ $action->acted_at->format('d M Y') }} @endif
                                </span>
                                @endif
                                @if ($isDone && $action->comment)
                                <p class="text-xs mt-1.5 italic rounded-lg p-2" style="color:rgba(255,255,255,0.7); background-color:rgba(0,0,0,0.15);">"{{ Str::limit($action->comment, 100) }}"</p>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ol>
                </div>
            </div>
            @endif

            {{-- Info card — plain white --}}
            <div class="card p-5">
                <h3 class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">{{ __('travel.info_card') }}</h3>
                <div class="space-y-3">
                    @foreach ([
                        [__('common.applicant'),   $tr->b_applicant_name ?? '—'],
                        [__('common.unit'),        $tr->unit?->name ?? '—'],
                        [__('common.destination'), $tr->b_destination ?? '—'],
                    ] as [$k, $v])
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5">{{ $k }}</p>
                        <p class="text-sm font-medium text-slate-800 leading-snug">{{ $v }}</p>
                    </div>
                    @endforeach
                    @if ($tr->b_departure_date)
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('travel.trip_dates') }}</p>
                        <p class="text-sm font-medium text-slate-800">
                            {{ $tr->b_departure_date->format('d M Y') }}
                            @if ($tr->b_return_date) &mdash; {{ $tr->b_return_date->format('d M Y') }} @endif
                        </p>
                    </div>
                    @endif
                    @if ($tr->submitted_at)
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-0.5">{{ __('travel.submitted') }}</p>
                        <p class="text-sm font-medium text-slate-800">{{ $tr->submitted_at->format('d M Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</div>

{{-- Confirmation modal --}}
<div x-show="modalOpen" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         @click.stop>
        <div class="flex items-start gap-4 mb-5">
            <div :class="modalIconClass" class="flex items-center justify-center w-10 h-10 rounded-full shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="modalIconPath"/></svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900" x-text="modalTitle"></h3>
                <p class="text-sm text-slate-500 mt-1" x-text="modalDescription"></p>
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <button @click="closeModal()" class="btn-secondary btn-sm">{{ __('common.cancel') }}</button>
            <button @click="confirmAction()" :class="modalBtnClass" class="btn btn-sm text-white px-4 py-2 rounded-lg font-semibold text-sm"><span x-text="modalConfirmLabel"></span></button>
        </div>
    </div>
</div>

{{-- Cancel form --}}
<form id="cancel-form" method="POST" action="{{ route('travel-requests.cancel', $travelRequest) }}" class="hidden">
    @csrf @method('DELETE')
</form>

<script>
    function approvalModal() {
        return {
            modalOpen: false, selectedDecision: '',
            modalTitle: '', modalDescription: '', modalConfirmLabel: '',
            modalBtnClass: '', modalIconClass: '', modalIconPath: '',
            pendingAction: null,
            openModal(decision) {
                this.selectedDecision = decision;
                const c = {
                    approved: { title: @json(__('travel.modal_approve_title')), desc: @json(__('travel.modal_approve_desc')), label: @json(__('travel.approve_btn')), btn: 'bg-emerald-600 hover:bg-emerald-700', icon: 'bg-emerald-100 text-emerald-700', path: 'M5 13l4 4L19 7', act: () => document.getElementById('approval-form').submit() },
                    returned:  { title: @json(__('travel.modal_return_title')), desc: @json(__('travel.modal_return_desc')), label: @json(__('travel.return_btn')), btn: 'bg-amber-500 hover:bg-amber-600', icon: 'bg-amber-100 text-amber-700', path: 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6', act: () => document.getElementById('approval-form').submit() },
                    rejected:  { title: @json(__('travel.modal_reject_title')), desc: @json(__('travel.modal_reject_desc')), label: @json(__('travel.reject_btn')), btn: 'bg-red-600 hover:bg-red-700', icon: 'bg-red-100 text-red-700', path: 'M6 18L18 6M6 6l12 12', act: () => document.getElementById('approval-form').submit() },
                    cancel:    { title: @json(__('travel.modal_cancel_title')), desc: @json(__('travel.modal_cancel_desc')), label: @json(__('travel.cancel_request')), btn: 'bg-red-600 hover:bg-red-700', icon: 'bg-red-100 text-red-700', path: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', act: () => document.getElementById('cancel-form').submit() },
                };
                const cfg = c[decision] || c.rejected;
                this.modalTitle = cfg.title; this.modalDescription = cfg.desc;
                this.modalConfirmLabel = cfg.label; this.modalBtnClass = cfg.btn;
                this.modalIconClass = cfg.icon; this.modalIconPath = cfg.path;
                this.pendingAction = cfg.act; this.modalOpen = true;
            },
            confirmCancel() { this.openModal('cancel'); },
            confirmAction() { this.modalOpen = false; this.pendingAction && this.pendingAction(); },
            closeModal() { this.modalOpen = false; this.selectedDecision = ''; },
        };
    }
</script>

</x-app-layout>
