<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('travel.edit_request') }}</h1>
            </div>
            <a href="{{ route('travel-requests.show', $travelRequest) }}" class="btn-ghost btn-sm">{{ __('users.back') }}</a>
        </div>
    </x-slot>

    <div class="p-6" x-data="formWizard({{ $errors->any() ? 1 : 0 }})">
        <div class="max-w-3xl mx-auto">

            {{-- Returned notice --}}
            @if ($travelRequest->status === \App\Models\TravelRequest::STATUS_RETURNED)
            @php $returnAction = $travelRequest->approvalActions->last(); @endphp
            <div class="card p-4 mb-6 flex gap-3 bg-orange-50 border-orange-200 border-l-4 border-l-orange-500">
                <div class="h-8 w-8 rounded-full bg-orange-100 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-orange-900">{{ __('travel.returned_title') }}</p>
                    @if ($returnAction?->comment)
                    <p class="text-sm text-orange-800 mt-1 leading-relaxed">{{ $returnAction->comment }}</p>
                    @endif
                    <p class="text-xs text-orange-600 mt-2 font-medium">{{ __('travel.returned_instruction') }}</p>
                </div>
            </div>
            @endif

            {{-- Step indicator --}}
            @php
                $steps = [
                    ['label' => 'A', 'title' => __('travel.step_a')],
                    ['label' => 'B', 'title' => __('travel.step_b')],
                    ['label' => 'C', 'title' => __('travel.step_c')],
                    ['label' => 'D', 'title' => __('travel.step_d')],
                    ['label' => 'E', 'title' => __('travel.step_e')],
                    ['label' => 'F', 'title' => __('travel.step_f')],
                    ['label' => 'G', 'title' => __('travel.step_g')],
                ];
                $tr = $travelRequest;
            @endphp

            <div class="mb-8">
                <div class="hidden sm:flex items-center">
                    @foreach ($steps as $i => $step)
                    <div class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center">
                            <button type="button" @click="goTo({{ $i }})"
                                :class="currentStep === {{ $i }}
                                    ? 'bg-indigo-600 text-white ring-4 ring-indigo-100 shadow-md shadow-indigo-200'
                                    : (currentStep > {{ $i }} ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-400 cursor-default')"
                                class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-150">
                                <template x-if="currentStep > {{ $i }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="currentStep <= {{ $i }}">
                                    <span>{{ $step['label'] }}</span>
                                </template>
                            </button>
                            <span :class="currentStep === {{ $i }} ? 'text-indigo-600 font-semibold' : (currentStep > {{ $i }} ? 'text-emerald-600' : 'text-slate-300')"
                                class="text-[10px] mt-1.5 whitespace-nowrap transition-colors">{{ $step['title'] }}</span>
                        </div>
                        @if ($i < count($steps) - 1)
                        <div :class="currentStep > {{ $i }} ? 'bg-emerald-400' : 'bg-slate-200'"
                            class="flex-1 h-0.5 mx-1.5 transition-colors duration-300"></div>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between sm:hidden">
                    <span class="text-xs text-slate-500">
                        <span x-text="currentStep + 1"></span> / {{ count($steps) }}
                    </span>
                    <span class="text-xs font-semibold text-indigo-600" x-text="stepTitles[currentStep]"></span>
                </div>
            </div>

            @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                <div class="flex items-center gap-2 mb-2 font-semibold">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('users.errors_title') }}
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('travel-requests.update', $travelRequest) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                {{-- Step 0 --}}
                <div x-show="currentStep === 0" x-cloak>
                    <div class="card overflow-hidden mb-5">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-amber-50">
                            <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 font-bold text-sm shrink-0">A</div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_a_title') }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('travel.section_a_read') }}</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-3 text-sm text-slate-700 leading-relaxed">
                            @foreach ([
                                __('travel.section_a_i'), __('travel.section_a_ii'), __('travel.section_a_iii'),
                                __('travel.section_a_iv'), __('travel.section_a_v'), __('travel.section_a_vi'),
                                __('travel.section_a_vii'), __('travel.section_a_viii'),
                            ] as $i => $rule)
                            <p><span class="font-bold text-amber-600">({{ ['i','ii','iii','iv','v','vi','vii','viii'][$i] }})</span> {{ $rule }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Step 1 --}}
                <div x-show="currentStep === 1" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">B</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_b_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-5">
                            @php $editUser = auth()->user(); @endphp
                            @foreach ([
                                ['(i)',   __('travel.b_name'),        'b_applicant_name', 'text',  old('b_applicant_name', $editUser->name),       true,  '',                           !empty($editUser->name)],
                                ['(ii)',  __('travel.b_phone'),       'b_phone',          'tel',   old('b_phone', $editUser->phone),               false, '+255 7XX XXX XXX',           !empty($editUser->phone)],
                                ['(iii)', __('travel.b_email'),       'b_email',          'email', old('b_email', $editUser->email),               false, '',                           !empty($editUser->email)],
                                ['(iv)',  __('travel.b_position'),    'b_position',       'text',  old('b_position', $editUser->job_title),         false, '',                           !empty($editUser->job_title)],
                                ['(v)',   __('travel.b_destination'), 'b_destination',    'text',  old('b_destination', $tr->b_destination),       true,  __('travel.b_destination_ph'), false],
                            ] as [$num, $label, $name, $type, $val, $req, $ph, $locked])
                            <div class="field">
                                <label class="label flex items-center gap-2">
                                    <span class="text-slate-400 mr-1.5">{{ $num }}</span>{{ $label }}
                                    @if($req)<span class="text-red-500 ml-0.5">*</span>@endif
                                    @if($locked)
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-400 border border-slate-200 ml-auto">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        from profile
                                    </span>
                                    @endif
                                </label>
                                <input type="{{ $type }}" name="{{ $name }}" value="{{ $val }}"
                                    class="input @error($name) input-error @enderror {{ $locked ? 'bg-slate-50 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                                    {{ $req ? 'required' : '' }}
                                    {{ $locked ? 'readonly' : '' }}
                                    @if($ph)placeholder="{{ $ph }}"@endif>
                            </div>
                            @endforeach
                            <div class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label class="label"><span class="text-slate-400 mr-1.5">(vi)</span> {{ __('travel.b_departure_date') }} <span class="text-red-500">*</span></label>
                                    <input type="date" name="b_departure_date"
                                        value="{{ old('b_departure_date', $tr->b_departure_date?->format('Y-m-d')) }}"
                                        class="input @error('b_departure_date') input-error @enderror" required>
                                </div>
                                <div class="field">
                                    <label class="label"><span class="text-slate-400 mr-1.5">(vii)</span> {{ __('travel.b_return_date') }} <span class="text-red-500">*</span></label>
                                    <input type="date" name="b_return_date"
                                        value="{{ old('b_return_date', $tr->b_return_date?->format('Y-m-d')) }}"
                                        class="input @error('b_return_date') input-error @enderror" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div x-show="currentStep === 2" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">C</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_c_title') }}</h3>
                        </div>
                        <div class="p-6">
                            <div class="field">
                                <label class="label">{{ __('travel.c_label') }}</label>
                                <textarea name="c_travel_source" rows="8" class="input resize-none leading-relaxed">{{ old('c_travel_source', $tr->c_travel_source) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div x-show="currentStep === 3" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">D</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_d_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="field">
                                <label class="label">{{ __('travel.d_institution') }}</label>
                                <textarea name="d_benefit_to_institution" rows="5" class="input resize-none leading-relaxed">{{ old('d_benefit_to_institution', $tr->d_benefit_to_institution) }}</textarea>
                            </div>
                            <div class="field">
                                <label class="label">{{ __('travel.d_nation') }}</label>
                                <textarea name="d_benefit_to_nation" rows="5" class="input resize-none leading-relaxed">{{ old('d_benefit_to_nation', $tr->d_benefit_to_nation) }}</textarea>
                            </div>
                            <div class="field">
                                <label class="label">{{ __('travel.d_consequences') }}</label>
                                <textarea name="d_consequences_if_rejected" rows="4" class="input resize-none leading-relaxed">{{ old('d_consequences_if_rejected', $tr->d_consequences_if_rejected) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div x-show="currentStep === 4" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">E</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_e_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="field">
                                <label class="label">{{ __('travel.e_transport') }}</label>
                                <textarea name="e_transport_costs" rows="3" class="input resize-none">{{ old('e_transport_costs', $tr->e_transport_costs) }}</textarea>
                            </div>
                            <div>
                                <label class="label">{{ __('travel.e_allowances') }}</label>
                                <div class="space-y-3">
                                    @foreach (['a', 'b', 'c', 'd'] as $letter)
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-slate-400 w-8 shrink-0">({{ $letter }})</span>
                                        <input type="text" name="e_allowance_{{ $letter }}"
                                            value="{{ old('e_allowance_' . $letter, $tr->{'e_allowance_' . $letter}) }}"
                                            class="input">
                                    </div>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <span class="text-sm text-slate-500 shrink-0">{{ __('travel.e_budget_line') }}</span>
                                    <input type="text" name="e_budget_line" value="{{ old('e_budget_line', $tr->e_budget_line) }}" class="input">
                                </div>
                            </div>
                            <div>
                                <label class="label">{{ __('travel.e_donor') }}</label>
                                <div class="space-y-3">
                                    @foreach (['i', 'ii', 'iii'] as $num)
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-slate-400 w-8 shrink-0">({{ $num }})</span>
                                        <input type="text" name="e_donor_cost_{{ $num }}"
                                            value="{{ old('e_donor_cost_' . $num, $tr->{'e_donor_cost_' . $num}) }}"
                                            class="input">
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="label">{{ __('travel.e_govt') }}</label>
                                <div class="space-y-3">
                                    @foreach (['i', 'ii', 'iii'] as $num)
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-slate-400 w-8 shrink-0">({{ $num }})</span>
                                        <input type="text" name="e_govt_cost_{{ $num }}"
                                            value="{{ old('e_govt_cost_' . $num, $tr->{'e_govt_cost_' . $num}) }}"
                                            class="input">
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="field">
                                <label class="label">{{ __('travel.e_other') }}</label>
                                <textarea name="e_other_costs" rows="3" class="input resize-none">{{ old('e_other_costs', $tr->e_other_costs) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 5 --}}
                <div x-show="currentStep === 5" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">F</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_f_title') }}</h3>
                        </div>
                        <div class="p-6">
                            <div class="field">
                                <label class="label">{{ __('travel.f_label') }}</label>
                                <textarea name="f_previous_travel_impact" rows="10" class="input resize-none leading-relaxed">{{ old('f_previous_travel_impact', $tr->f_previous_travel_impact) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 6 --}}
                <div x-show="currentStep === 6" x-cloak>
                    <div class="card overflow-hidden mb-5">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">G</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_g_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="field relative"
                                 x-data="{
                                     open: false,
                                     search: '{{ old('g_handover_officer_name', $tr->g_handover_officer_name) }}',
                                     selectedName: '{{ old('g_handover_officer_name', $tr->g_handover_officer_name) }}',
                                     selectedTitle: '{{ old('g_handover_officer_title', $tr->g_handover_officer_title) }}',
                                     users: {{ Js::from($handoverUsers) }},
                                     get filtered() {
                                         if (!this.search) return this.users;
                                         const q = this.search.toLowerCase();
                                         return this.users.filter(u => u.name.toLowerCase().includes(q) || u.title.toLowerCase().includes(q));
                                     },
                                     select(u) { this.selectedName = u.name; this.selectedTitle = u.title; this.search = u.name; this.open = false; }
                                 }"
                                 @click.outside="open = false">
                                <label class="label">{{ __('travel.g_officer_name') }}</label>
                                <input type="hidden" name="g_handover_officer_name" :value="selectedName">
                                <input type="hidden" name="g_handover_officer_title" :value="selectedTitle">
                                <div class="relative">
                                    <input type="text" x-model="search"
                                           @focus="open = true" @input="open = true; selectedName = ''; selectedTitle = ''"
                                           class="input pr-9" placeholder="Search by name or title…" autocomplete="off">
                                    <button type="button" @click="open = !open"
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </div>
                                <div x-show="open" x-cloak
                                     class="absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                                    <template x-for="u in filtered" :key="u.id">
                                        <button type="button" @click="select(u)"
                                                class="w-full text-left px-3 py-2.5 hover:bg-indigo-50 flex flex-col gap-0.5">
                                            <span class="text-sm font-medium text-slate-800" x-text="u.name"></span>
                                            <span class="text-xs text-slate-400" x-text="u.title"></span>
                                        </button>
                                    </template>
                                    <div x-show="filtered.length === 0" class="px-3 py-3 text-sm text-slate-400 text-center">No results</div>
                                </div>
                                <p x-show="selectedTitle" x-cloak class="text-xs text-slate-400 mt-1" x-text="'Title: ' + selectedTitle"></p>
                            </div>

                            {{-- File upload — PDF only --}}
                            <div x-data="{ fileName: '', dragOver: false, fileError: '' }" class="field">
                                <label class="label">
                                    {{ __('travel.g_upload') }} <span class="font-normal text-slate-400">PDF only, max 5 MB</span>
                                    @if ($tr->g_handover_document)
                                    <span class="font-normal text-emerald-600 ml-1">{{ __('travel.g_existing_file') }}</span>
                                    @endif
                                </label>
                                <label
                                    :class="dragOver ? 'border-indigo-400 bg-indigo-50' : (fileName ? 'border-emerald-400 bg-emerald-50' : (fileError ? 'border-red-400 bg-red-50' : 'border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/40'))"
                                    class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed rounded-xl cursor-pointer transition-all"
                                    @dragover.prevent="dragOver = true"
                                    @dragleave="dragOver = false"
                                    @drop.prevent="
                                        dragOver = false;
                                        const f = $event.dataTransfer.files[0];
                                        if (f && f.type !== 'application/pdf') { fileError = 'Only PDF files are accepted.'; fileName = ''; }
                                        else { fileError = ''; fileName = f?.name ?? ''; }
                                    ">
                                    <div x-show="!fileName && !fileError" class="flex flex-col items-center gap-2 text-slate-400">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <span class="text-sm">{{ __('travel.g_click_drag') }}</span>
                                    </div>
                                    <div x-show="fileError" class="flex items-center gap-2 text-red-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="text-sm font-medium" x-text="fileError"></span>
                                    </div>
                                    <div x-show="fileName" class="flex items-center gap-3 text-emerald-700">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="text-sm font-medium" x-text="fileName"></span>
                                            <p class="text-xs text-emerald-600">{{ __('travel.g_file_selected') }}</p>
                                        </div>
                                    </div>
                                    <input type="file" name="g_handover_document" accept=".pdf" class="hidden"
                                        @change="
                                            const f = $event.target.files[0];
                                            if (f && f.type !== 'application/pdf') { fileError = 'Only PDF files are accepted.'; fileName = ''; $event.target.value = ''; }
                                            else { fileError = ''; fileName = f?.name ?? ''; }
                                        ">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Navigation --}}
                <div class="flex items-center justify-between mt-6 pt-5 border-t border-slate-200">
                    <button type="button" @click="prev()" x-show="currentStep > 0" class="btn-ghost">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('users.back') }}
                    </button>
                    <div x-show="currentStep === 0"></div>
                    <div class="flex items-center gap-3">
                        <button type="submit" name="action" value="draft" x-show="currentStep > 0" class="btn-secondary btn-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            {{ __('travel.save_draft') }}
                        </button>
                        <button type="button" @click="next()" x-show="currentStep < 6" class="btn-primary">
                            <span x-text="currentStep === 0 ? @json(__('travel.understood_next')) : @json(__('travel.next'))"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                        <button type="submit" name="action" value="submit" x-show="currentStep === 6" class="btn-success">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('travel.resubmit') }}
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        function formWizard(initialStep) {
            return {
                currentStep: initialStep,
                stepTitles: @json(array_column($steps, 'title')),
                next() { if (this.currentStep < 6) { this.currentStep++; window.scrollTo({ top: 0, behavior: 'smooth' }); } },
                prev() { if (this.currentStep > 0) { this.currentStep--; window.scrollTo({ top: 0, behavior: 'smooth' }); } },
                goTo(step) { if (step <= this.currentStep) { this.currentStep = step; window.scrollTo({ top: 0, behavior: 'smooth' }); } },
            };
        }
    </script>
</x-app-layout>
