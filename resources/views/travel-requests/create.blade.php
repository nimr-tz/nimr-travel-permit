<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">{{ __('travel.new_request') }}</h1>
            <p class="page-sub">{{ __('travel.form_subtitle') }} — {{ auth()->user()->unit?->name ?? '—' }}</p>
        </div>
    </x-slot>

    <div class="p-6" x-data="formWizard({{ $errors->any() ? 1 : 0 }})">
        <div class="max-w-3xl mx-auto">

            {{-- ── Step indicator ──────────────────────────────────────── --}}
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
            @endphp

            <div class="mb-8">
                {{-- Desktop stepper --}}
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

                {{-- Mobile step label --}}
                <div class="flex items-center justify-between sm:hidden">
                    <span class="text-xs text-slate-500" x-text="'{{ __('travel.step_x_of_y', ['x' => '', 'y' => count($steps)]) }}'.replace('', currentStep + 1)">
                    </span>
                    <span class="text-xs font-semibold text-indigo-600" x-text="stepTitles[currentStep]"></span>
                    <div class="flex gap-1">
                        @for ($i = 0; $i < count($steps); $i++)
                        <div :class="currentStep === {{ $i }} ? 'bg-indigo-600 w-4' : (currentStep > {{ $i }} ? 'bg-emerald-400' : 'bg-slate-200')"
                            class="h-1.5 rounded-full transition-all duration-200 w-1.5"></div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Validation errors --}}
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

            <form method="POST" action="{{ route('travel-requests.store') }}" enctype="multipart/form-data" id="travel-form">
                @csrf

                {{-- ── Step 0: Terms ────────────────────────────────────── --}}
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
                                __('travel.section_a_i'),
                                __('travel.section_a_ii'),
                                __('travel.section_a_iii'),
                                __('travel.section_a_iv'),
                                __('travel.section_a_v'),
                                __('travel.section_a_vi'),
                                __('travel.section_a_vii'),
                                __('travel.section_a_viii'),
                            ] as $i => $rule)
                            <p><span class="font-bold text-amber-600">({{ ['i','ii','iii','iv','v','vi','vii','viii'][$i] }})</span> {{ $rule }}</p>
                            @endforeach
                        </div>
                    </div>
                    <div class="card p-4 flex items-start gap-3 bg-indigo-50 border-indigo-200">
                        <div class="h-7 w-7 rounded-full bg-indigo-100 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-sm text-indigo-800">
                            {!! __('travel.section_a_unit_note', ['unit' => '<strong>' . (auth()->user()->unit?->name ?? '—') . '</strong>']) !!}
                        </p>
                    </div>
                </div>

                {{-- ── Step 1: Employee Info ─────────────────────────────── --}}
                <div x-show="currentStep === 1" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">B</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_b_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-5">
                            @php
                                $flds = [
                                    ['(i)',   __('travel.b_name'),        'b_applicant_name', 'text',  old('b_applicant_name', $user->name), true,  ''],
                                    ['(ii)',  __('travel.b_phone'),       'b_phone',          'tel',   old('b_phone', $user->phone),          false, '+255 7XX XXX XXX'],
                                    ['(iii)', __('travel.b_email'),       'b_email',          'email', old('b_email', $user->email),           false, ''],
                                    ['(iv)',  __('travel.b_position'),    'b_position',       'text',  old('b_position', $user->job_title),    false, ''],
                                    ['(v)',   __('travel.b_destination'), 'b_destination',    'text',  old('b_destination'),                   true,  __('travel.b_destination_ph')],
                                ];
                            @endphp
                            @foreach ($flds as [$num, $label, $name, $type, $val, $req, $ph])
                            <div class="field">
                                <label class="label">
                                    <span class="text-slate-400 mr-1.5">{{ $num }}</span>
                                    {{ $label }}
                                    @if($req) <span class="text-red-500 ml-0.5">*</span> @endif
                                </label>
                                <input type="{{ $type }}" name="{{ $name }}" value="{{ $val }}"
                                    class="input @error($name) input-error @enderror"
                                    {{ $req ? 'required' : '' }}
                                    @if($ph) placeholder="{{ $ph }}" @endif>
                            </div>
                            @endforeach
                            <div class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label class="label"><span class="text-slate-400 mr-1.5">(vi)</span> {{ __('travel.b_departure_date') }} <span class="text-red-500">*</span></label>
                                    <input type="date" name="b_departure_date" value="{{ old('b_departure_date') }}"
                                        min="{{ now()->addDays(1)->format('Y-m-d') }}"
                                        class="input @error('b_departure_date') input-error @enderror" required>
                                </div>
                                <div class="field">
                                    <label class="label"><span class="text-slate-400 mr-1.5">(vii)</span> {{ __('travel.b_return_date') }} <span class="text-red-500">*</span></label>
                                    <input type="date" name="b_return_date" value="{{ old('b_return_date') }}"
                                        class="input @error('b_return_date') input-error @enderror" required>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400">{{ __('travel.b_note') }}</p>
                        </div>
                    </div>
                </div>

                {{-- ── Step 2: Travel Source ────────────────────────────── --}}
                <div x-show="currentStep === 2" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">C</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_c_title') }}</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-slate-500 mb-4 leading-relaxed">{{ __('travel.c_desc') }}</p>
                            <div class="field">
                                <label class="label">{{ __('travel.c_label') }} <span class="text-red-500">*</span></label>
                                <textarea name="c_travel_source" rows="8" class="input resize-none leading-relaxed"
                                    placeholder="{{ __('travel.c_label') }}...">{{ old('c_travel_source') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Step 3: Benefits ─────────────────────────────────── --}}
                <div x-show="currentStep === 3" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">D</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_d_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <h4 class="text-sm font-semibold text-slate-700">{{ __('travel.d_benefits_sub') }}</h4>
                            <div class="field">
                                <label class="label">{{ __('travel.d_institution') }}</label>
                                <textarea name="d_benefit_to_institution" rows="5" class="input resize-none leading-relaxed"
                                    placeholder="...">{{ old('d_benefit_to_institution') }}</textarea>
                            </div>
                            <div class="field">
                                <label class="label">{{ __('travel.d_nation') }}</label>
                                <textarea name="d_benefit_to_nation" rows="5" class="input resize-none leading-relaxed"
                                    placeholder="...">{{ old('d_benefit_to_nation') }}</textarea>
                            </div>
                            <div class="field">
                                <label class="label">{{ __('travel.d_consequences') }}</label>
                                <textarea name="d_consequences_if_rejected" rows="4" class="input resize-none leading-relaxed"
                                    placeholder="...">{{ old('d_consequences_if_rejected') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Step 4: Costs ────────────────────────────────────── --}}
                <div x-show="currentStep === 4" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">E</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_e_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="field">
                                <label class="label">{{ __('travel.e_transport') }} <span class="font-normal text-slate-400">{{ __('travel.e_transport_hint') }}</span></label>
                                <textarea name="e_transport_costs" rows="3" class="input resize-none"
                                    placeholder="...">{{ old('e_transport_costs') }}</textarea>
                            </div>

                            <div>
                                <label class="label">{{ __('travel.e_allowances') }}</label>
                                <div class="space-y-3">
                                    @foreach (['a', 'b', 'c', 'd'] as $letter)
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-slate-400 w-8 shrink-0">({{ $letter }})</span>
                                        <input type="text" name="e_allowance_{{ $letter }}" value="{{ old('e_allowance_' . $letter) }}"
                                            class="input" placeholder="...">
                                    </div>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <span class="text-sm text-slate-500 shrink-0">{{ __('travel.e_budget_line') }}</span>
                                    <input type="text" name="e_budget_line" value="{{ old('e_budget_line') }}"
                                        class="input" placeholder="...">
                                </div>
                            </div>

                            <div>
                                <label class="label">{{ __('travel.e_donor') }}</label>
                                <div class="space-y-3">
                                    @foreach (['i', 'ii', 'iii'] as $num)
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-slate-400 w-8 shrink-0">({{ $num }})</span>
                                        <input type="text" name="e_donor_cost_{{ $num }}" value="{{ old('e_donor_cost_' . $num) }}"
                                            class="input" placeholder="...">
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
                                        <input type="text" name="e_govt_cost_{{ $num }}" value="{{ old('e_govt_cost_' . $num) }}"
                                            class="input" placeholder="...">
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="field">
                                <label class="label">{{ __('travel.e_other') }}</label>
                                <textarea name="e_other_costs" rows="3" class="input resize-none"
                                    placeholder="...">{{ old('e_other_costs') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Step 5: Impact ───────────────────────────────────── --}}
                <div x-show="currentStep === 5" x-cloak>
                    <div class="card overflow-hidden">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">F</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_f_title') }}</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-slate-500 mb-4 leading-relaxed">{{ __('travel.f_desc') }}</p>
                            <div class="field">
                                <label class="label">{{ __('travel.f_label') }}</label>
                                <textarea name="f_previous_travel_impact" rows="10" class="input resize-none leading-relaxed"
                                    placeholder="...">{{ old('f_previous_travel_impact') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Step 6: Handover ─────────────────────────────────── --}}
                <div x-show="currentStep === 6" x-cloak>
                    <div class="card overflow-hidden mb-5">
                        <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm shrink-0">G</div>
                            <h3 class="text-sm font-bold text-slate-900">{{ __('travel.section_g_title') }}</h3>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="field">
                                    <label class="label">{{ __('travel.g_officer_name') }}</label>
                                    <input type="text" name="g_handover_officer_name" value="{{ old('g_handover_officer_name') }}"
                                        class="input" placeholder="...">
                                </div>
                                <div class="field">
                                    <label class="label">{{ __('travel.g_officer_title') }}</label>
                                    <input type="text" name="g_handover_officer_title" value="{{ old('g_handover_officer_title') }}"
                                        class="input" placeholder="...">
                                </div>
                            </div>

                            {{-- File upload --}}
                            <div x-data="{ fileName: '', dragOver: false }" class="field">
                                <label class="label">{{ __('travel.g_upload') }} <span class="font-normal text-slate-400">{{ __('travel.g_upload_hint') }}</span></label>
                                <label
                                    :class="dragOver ? 'border-indigo-400 bg-indigo-50' : (fileName ? 'border-emerald-400 bg-emerald-50' : 'border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/40')"
                                    class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed rounded-xl cursor-pointer transition-all"
                                    @dragover.prevent="dragOver = true"
                                    @dragleave="dragOver = false"
                                    @drop.prevent="dragOver = false; fileName = $event.dataTransfer.files[0]?.name ?? ''">
                                    <div x-show="!fileName" class="flex flex-col items-center gap-2 text-slate-400">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <span class="text-sm">{{ __('travel.g_click_drag') }}</span>
                                    </div>
                                    <div x-show="fileName" class="flex items-center gap-3 text-emerald-700">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <div>
                                            <span class="text-sm font-medium" x-text="fileName"></span>
                                            <p class="text-xs text-emerald-600">{{ __('travel.g_file_selected') }}</p>
                                        </div>
                                    </div>
                                    <input type="file" name="g_handover_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden"
                                        @change="fileName = $event.target.files[0]?.name ?? ''">
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Pre-submit checklist --}}
                    <div class="card p-5 bg-emerald-50 border-emerald-200">
                        <h4 class="text-sm font-bold text-emerald-800 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('travel.checklist_title') }}
                        </h4>
                        <ul class="space-y-1.5 text-sm text-emerald-700">
                            @foreach ([__('travel.checklist_1'), __('travel.checklist_2'), __('travel.checklist_3')] as $item)
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                {{-- ── Navigation ───────────────────────────────────────── --}}
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
                            {{ __('travel.submit_request') }}
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
