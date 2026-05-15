<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-800">Hariri — {{ $travelRequest->request_number }}</h2>
            <a href="{{ route('travel-requests.show', $travelRequest) }}" class="text-sm text-blue-700 hover:text-blue-900">← Rudi</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto px-6" x-data="formWizard()">

            {{-- Step indicator --}}
            <div class="flex items-center mb-12">
                @php
                    $steps = [
                        ['label' => 'A', 'title' => 'Utangulizi'],
                        ['label' => 'B', 'title' => 'Mtumishi'],
                        ['label' => 'C', 'title' => 'Chanzo'],
                        ['label' => 'D', 'title' => 'Faida'],
                        ['label' => 'E', 'title' => 'Gharama'],
                        ['label' => 'F', 'title' => 'Safari za Nyuma'],
                        ['label' => 'G', 'title' => 'Handover'],
                    ];
                    $tr = $travelRequest;
                @endphp
                @foreach ($steps as $i => $step)
                    <div class="flex items-center {{ $i < count($steps) - 1 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center">
                            <div :class="currentStep === {{ $i }}
                                    ? 'bg-blue-700 text-white'
                                    : (currentStep > {{ $i }} ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400')"
                                class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors">
                                <template x-if="currentStep > {{ $i }}">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="currentStep <= {{ $i }}">
                                    <span>{{ $step['label'] }}</span>
                                </template>
                            </div>
                            <span :class="currentStep === {{ $i }} ? 'text-blue-700 font-semibold' : (currentStep > {{ $i }} ? 'text-blue-400' : 'text-gray-300')"
                                class="text-xs mt-1.5 hidden sm:block whitespace-nowrap">{{ $step['title'] }}</span>
                        </div>
                        @if ($i < count($steps) - 1)
                            <div :class="currentStep > {{ $i }} ? 'bg-green-400' : 'bg-gray-200'"
                                class="flex-1 h-px mx-2 transition-colors"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-base">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('travel-requests.update', $travelRequest) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                @php
                    $fieldClass    = 'flex-1 border-0 border-b-2 border-gray-300 focus:border-blue-600 focus:ring-0 text-lg py-2 px-0 bg-transparent placeholder-gray-300';
                    $labelClass    = 'text-lg w-96 shrink-0 text-gray-600';
                    $numClass      = 'text-lg w-10 shrink-0 text-gray-400';
                    $textareaClass = 'w-full border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 text-lg bg-white resize-none p-4 leading-relaxed shadow-sm';
                @endphp

                {{-- STEP 0 — A --}}
                <div x-show="currentStep === 0" x-cloak>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-600 mb-8">Sehemu A — Utangulizi</h3>
                    <div class="text-lg space-y-5 text-gray-600 leading-relaxed">
                        <p><span class="font-semibold text-gray-700">(i)</span> Watumishi wote wanaotarajia kusafiri ndani ya nchi wanapaswa kujaza kikamilifu fomu hii siku 14 kabla ya safari.</p>
                        <p><span class="font-semibold text-gray-700">(ii)</span> Pamoja na fomu hii, viambatisho muhimu vinavyothibitisha safari lazima viwepo.</p>
                        <p><span class="font-semibold text-gray-700">(iii)</span> Fomu hii itajazwa na kila Mtumishi wa Taasisi atakayesafiri kwa shughuli za kiofisi ndani ya nchi.</p>
                        <p><span class="font-semibold text-gray-700">(iv)</span> Fomu ambayo haikujazwa kikamilifu haitashughulikiwa na hivyo Mtumishi atakuwa amejinyima ruhusa mwenyewe ya kusafiri.</p>
                        <p><span class="font-semibold text-gray-700">(v)</span> Ndani ya kipindi cha wiki mbili baada ya kurudi kutoka safarini Mtumishi anatakiwa kuwasilisha ripoti ya safari kwa Mkurugenzi Mkuu.</p>
                        <p><span class="font-semibold text-gray-700">(vi)</span> Safari za dharura zitatumia utaratibu wa dharura.</p>
                        <p><span class="font-semibold text-gray-700">(vii)</span> Fomu hii itajazwa nakala mbili (2).</p>
                    </div>
                </div>

                {{-- STEP 1 — B --}}
                <div x-show="currentStep === 1" x-cloak>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-600 mb-8">Sehemu B — Taarifa za Mtumishi Anayesafiri</h3>
                    <div class="space-y-8">
                        <div class="flex items-baseline gap-3">
                            <span class="{{ $numClass }}">(i)</span>
                            <label class="{{ $labelClass }}">Jina la Mtumishi anayesafiri</label>
                            <input type="text" name="b_applicant_name" value="{{ old('b_applicant_name', $tr->b_applicant_name) }}" class="{{ $fieldClass }}" required>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="{{ $numClass }}">(ii)</span>
                            <label class="{{ $labelClass }}">Simu</label>
                            <input type="text" name="b_phone" value="{{ old('b_phone', $tr->b_phone) }}" class="{{ $fieldClass }}">
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="{{ $numClass }}">(iii)</span>
                            <label class="{{ $labelClass }}">Barua Pepe</label>
                            <input type="email" name="b_email" value="{{ old('b_email', $tr->b_email) }}" class="{{ $fieldClass }}">
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="{{ $numClass }}">(iv)</span>
                            <label class="{{ $labelClass }}">Cheo</label>
                            <input type="text" name="b_position" value="{{ old('b_position', $tr->b_position) }}" class="{{ $fieldClass }}">
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="{{ $numClass }}">(v)</span>
                            <label class="{{ $labelClass }}">Mikoa/Mkoa/Wilaya anapokwenda</label>
                            <input type="text" name="b_destination" value="{{ old('b_destination', $tr->b_destination) }}" class="{{ $fieldClass }}" required>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="{{ $numClass }}">(vi)</span>
                            <label class="{{ $labelClass }}">Tarehe ya Kuondoka</label>
                            <input type="date" name="b_departure_date" value="{{ old('b_departure_date', $tr->b_departure_date?->format('Y-m-d')) }}" class="{{ $fieldClass }}" required>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <span class="{{ $numClass }}">(vii)</span>
                            <label class="{{ $labelClass }}">Tarehe ya Kurudi</label>
                            <input type="date" name="b_return_date" value="{{ old('b_return_date', $tr->b_return_date?->format('Y-m-d')) }}" class="{{ $fieldClass }}" required>
                        </div>
                    </div>
                </div>

                {{-- STEP 2 — C --}}
                <div x-show="currentStep === 2" x-cloak>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-600 mb-3">Sehemu C — Chanzo cha Safari</h3>
                    <p class="text-lg text-gray-500 mb-6 leading-relaxed">Ni nani aliyeanzisha safari? Je ni mtumishi? Je ni Serikali au Mwaliko mwingine? Eleza kwa ufafanuzi na ambatisha barua ya mwaliko.</p>
                    <textarea name="c_travel_source" rows="10" class="{{ $textareaClass }}">{{ old('c_travel_source', $tr->c_travel_source) }}</textarea>
                </div>

                {{-- STEP 3 — D --}}
                <div x-show="currentStep === 3" x-cloak>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-600 mb-8">Sehemu D — Faida ya Safari na Athari</h3>
                    <p class="text-lg text-gray-700 font-medium mb-4">(i) Faida / Tija na Umuhimu wa safari:</p>
                    <div class="mb-8">
                        <p class="text-lg text-gray-500 mb-2">(a) Kwa Taasisi:</p>
                        <textarea name="d_benefit_to_institution" rows="5" class="{{ $textareaClass }}">{{ old('d_benefit_to_institution', $tr->d_benefit_to_institution) }}</textarea>
                    </div>
                    <div class="mb-8">
                        <p class="text-lg text-gray-500 mb-2">(b) Kwa Taifa:</p>
                        <textarea name="d_benefit_to_nation" rows="5" class="{{ $textareaClass }}">{{ old('d_benefit_to_nation', $tr->d_benefit_to_nation) }}</textarea>
                    </div>
                    <div>
                        <p class="text-lg text-gray-700 font-medium mb-2">(ii) Athari zitakazokuwepo kama safari haikupitishwa:</p>
                        <textarea name="d_consequences_if_rejected" rows="5" class="{{ $textareaClass }}">{{ old('d_consequences_if_rejected', $tr->d_consequences_if_rejected) }}</textarea>
                    </div>
                </div>

                {{-- STEP 4 — E --}}
                <div x-show="currentStep === 4" x-cloak>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-600 mb-8">Sehemu E — Gharama za Safari</h3>

                    <div class="mb-8">
                        <p class="text-lg text-gray-700 font-medium mb-3">(i) Gharama za Usafiri <span class="font-normal text-gray-500">(Taja kiwango)</span></p>
                        <textarea name="e_transport_costs" rows="3" class="{{ $textareaClass }}">{{ old('e_transport_costs', $tr->e_transport_costs) }}</textarea>
                    </div>

                    <div class="mb-8">
                        <p class="text-lg text-gray-700 font-medium mb-4">(ii) Posho zote za safari <span class="font-normal text-gray-500">(Taja kiwango)</span></p>
                        <div class="ml-4 space-y-4 mb-5">
                            @foreach (['a', 'b', 'c', 'd'] as $letter)
                            <div class="flex items-baseline gap-3">
                                <span class="{{ $numClass }}">({{ $letter }})</span>
                                <input type="text" name="e_allowance_{{ $letter }}" value="{{ old('e_allowance_' . $letter, $tr->{'e_allowance_' . $letter}) }}" class="{{ $fieldClass }}">
                            </div>
                            @endforeach
                        </div>
                        <div class="flex items-baseline gap-3 ml-4">
                            <span class="text-lg text-gray-500 shrink-0">Kifungu cha Safari:</span>
                            <input type="text" name="e_budget_line" value="{{ old('e_budget_line', $tr->e_budget_line) }}" class="{{ $fieldClass }}">
                        </div>
                    </div>

                    <div class="mb-8">
                        <p class="text-lg text-gray-700 font-medium mb-4">(iii) Mlipaji wa gharama <span class="font-normal text-gray-500">(kama zipo za Serikali, taja kiasi)</span></p>
                        <div class="ml-4 mb-5">
                            <p class="text-lg text-gray-500 mb-3">(a) Gharama za Wafadhili:</p>
                            <div class="space-y-4 ml-4">
                                @foreach (['i', 'ii', 'iii'] as $num)
                                <div class="flex items-baseline gap-3">
                                    <span class="{{ $numClass }}">({{ $num }})</span>
                                    <input type="text" name="e_donor_cost_{{ $num }}" value="{{ old('e_donor_cost_' . $num, $tr->{'e_donor_cost_' . $num}) }}" class="{{ $fieldClass }}">
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-lg text-gray-500 mb-3">(b) Gharama za Serikali:</p>
                            <div class="space-y-4 ml-4">
                                @foreach (['i', 'ii', 'iii'] as $num)
                                <div class="flex items-baseline gap-3">
                                    <span class="{{ $numClass }}">({{ $num }})</span>
                                    <input type="text" name="e_govt_cost_{{ $num }}" value="{{ old('e_govt_cost_' . $num, $tr->{'e_govt_cost_' . $num}) }}" class="{{ $fieldClass }}">
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-lg text-gray-700 font-medium mb-2">(iv) Gharama zingine <span class="font-normal text-gray-500">(Taja kiwango)</span></p>
                        <textarea name="e_other_costs" rows="3" class="{{ $textareaClass }}">{{ old('e_other_costs', $tr->e_other_costs) }}</textarea>
                    </div>
                </div>

                {{-- STEP 5 — F --}}
                <div x-show="currentStep === 5" x-cloak>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-600 mb-3">Sehemu F — Manufaa ya Safari za Nyuma</h3>
                    <p class="text-lg text-gray-500 mb-6">Impact Assessment of the Previous Travels</p>
                    <textarea name="f_previous_travel_impact" rows="10" class="{{ $textareaClass }}">{{ old('f_previous_travel_impact', $tr->f_previous_travel_impact) }}</textarea>
                </div>

                {{-- STEP 6 — G --}}
                <div x-show="currentStep === 6" x-cloak>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-blue-600 mb-3">Sehemu G — Handover Note</h3>
                    <p class="text-lg text-gray-500 mb-8">Taja jina la afisa utakayemkaimisha majukumu yako na pakia hati ya makubaliano.</p>

                    <div class="space-y-8 mb-10">
                        <div class="flex items-baseline gap-3">
                            <label class="text-lg text-gray-600 w-16 shrink-0">Jina:</label>
                            <input type="text" name="g_handover_officer_name" value="{{ old('g_handover_officer_name', $tr->g_handover_officer_name) }}" class="{{ $fieldClass }}">
                        </div>
                        <div class="flex items-baseline gap-3">
                            <label class="text-lg text-gray-600 w-16 shrink-0">Cheo:</label>
                            <input type="text" name="g_handover_officer_title" value="{{ old('g_handover_officer_title', $tr->g_handover_officer_title) }}" class="{{ $fieldClass }}">
                        </div>
                    </div>

                    <div>
                        <p class="text-lg text-gray-700 font-medium mb-2">Pakia Hati ya Handover Note</p>
                        <p class="text-base text-gray-500 mb-4">Faili inayokubalika: PDF, Word, au picha (JPG/PNG). Ukubwa wa juu: 5MB.
                            @if ($tr->g_handover_document)
                                <span class="text-blue-600">Hati iliyopo tayari itabadilishwa ukipakia mpya.</span>
                            @endif
                        </p>
                        <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition"
                            x-data="{ fileName: '{{ $tr->g_handover_document ? basename($tr->g_handover_document) : '' }}' }">
                            <div x-show="!fileName" class="flex flex-col items-center gap-2 text-gray-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span class="text-base">Bonyeza hapa kupakia hati</span>
                            </div>
                            <div x-show="fileName" class="flex items-center gap-3 text-blue-700">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="text-base font-medium" x-text="fileName"></span>
                            </div>
                            <input type="file" name="g_handover_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="hidden"
                                @change="fileName = $event.target.files[0]?.name ?? ''">
                        </label>
                    </div>

                    <div class="mt-10 pt-6 border-t border-gray-100 space-y-2">
                        <p class="text-base text-gray-500">• Hakikisha taarifa zote zimejazwa kikamilifu kabla ya kuwasilisha.</p>
                        <p class="text-base text-gray-500">• Ambatisha barua ya mwaliko na viambatisho vinavyohusika.</p>
                        <p class="text-base text-gray-500">• Fomu inajazwa siku 14 kabla ya safari.</p>
                    </div>
                </div>

                {{-- Navigation --}}
                <div class="flex items-center justify-between mt-12 pt-6 border-t border-gray-100">
                    <button type="button" @click="prev()" x-show="currentStep > 0"
                        class="flex items-center gap-1.5 text-lg text-gray-500 hover:text-gray-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Rudi
                    </button>
                    <div x-show="currentStep === 0"></div>

                    <div class="flex items-center gap-4">
                        <button type="submit" name="action" value="draft" x-show="currentStep > 0"
                            class="text-lg text-gray-400 hover:text-gray-600 transition">
                            Hifadhi Rasimu
                        </button>
                        <button type="button" @click="next()" x-show="currentStep < 6"
                            class="flex items-center gap-1.5 px-6 py-2.5 bg-blue-700 text-white text-base font-semibold rounded-md hover:bg-blue-800 transition">
                            <span x-text="currentStep === 0 ? 'Nimeelewa' : 'Endelea'"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <button type="submit" name="action" value="submit" x-show="currentStep === 6"
                            class="px-6 py-2.5 bg-blue-700 text-white text-base font-semibold rounded-md hover:bg-blue-800 transition">
                            Wasilisha Ombi
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <script>
        function formWizard() {
            return {
                currentStep: {{ $errors->any() ? 1 : 0 }},
                next() {
                    if (this.currentStep < 6) {
                        this.currentStep++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                prev() {
                    if (this.currentStep > 0) {
                        this.currentStep--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                }
            }
        }
    </script>
</x-app-layout>
