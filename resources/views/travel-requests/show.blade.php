<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-800">{{ $travelRequest->request_number }}</h2>
                <p class="mt-1 text-xs text-gray-500">Fomu ya Maombi ya Ruhusa ya Kusafiri Ndani ya Nchi</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('travel-requests.print', $travelRequest) }}" target="_blank"
                    class="px-3 py-1.5 text-xs font-semibold border border-gray-300 rounded-md text-gray-600 hover:bg-gray-50">
                    🖨 Chapisha
                </a>
                <a href="{{ route('travel-requests.index') }}" class="text-sm text-blue-700 hover:text-blue-900">← Rudi</a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-50 border border-green-300 rounded-lg text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Status Bar --}}
            <div class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    @php
                        $statusColors = [
                            'draft'   => 'bg-gray-100 text-gray-700',
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved'=> 'bg-green-100 text-green-800',
                            'rejected'=> 'bg-red-100 text-red-800',
                        ];
                        $statusLabels = [
                            'draft'   => 'Rasimu',
                            'pending' => 'Inasubiri Idhini',
                            'approved'=> 'Imeidhinishwa',
                            'rejected'=> 'Imekataliwa',
                        ];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$travelRequest->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $statusLabels[$travelRequest->status] ?? ucfirst($travelRequest->status) }}
                    </span>
                    @if ($travelRequest->submitted_at)
                        <span class="text-xs text-gray-500">Iliwasilishwa: {{ $travelRequest->submitted_at->format('d M Y, H:i') }}</span>
                    @endif
                </div>

                @if ($travelRequest->status === 'pending' && $travelRequest->currentApprover)
                    <div class="text-xs text-gray-600">
                        Inasubiri: <span class="font-semibold text-gray-800">{{ $travelRequest->currentApprover->name }}</span>
                        <span class="text-gray-400">({{ $travelRequest->currentApprover->job_title }})</span>
                    </div>
                @endif

                @if ($travelRequest->status === 'draft' && $travelRequest->requester_id === auth()->id())
                    <a href="{{ route('travel-requests.edit', $travelRequest) }}"
                        class="px-4 py-1.5 text-xs font-semibold bg-blue-700 text-white rounded hover:bg-blue-800">
                        Hariri Rasimu
                    </a>
                @endif
            </div>

            {{-- ============================================================ --}}
            {{-- APPROVAL CHAIN PROGRESS                                       --}}
            {{-- ============================================================ --}}
            @if ($travelRequest->approval_chain)
            <div class="bg-white shadow-sm rounded-lg p-5">
                <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-4">Mtiririko wa Idhini</h3>
                <div class="flex flex-wrap gap-2 items-center">
                    @php
                        $actions = $travelRequest->approvalActions->keyBy('stage');
                        $stageLabels = [
                            'supervisor' => 'Msimamizi / Mkuu wa Kitengo',
                            'director'   => 'Mkurugenzi',
                            'final'      => 'Idhini ya Mwisho',
                            'hr'         => 'Rasilimali Watu (Taarifa)',
                        ];
                    @endphp
                    @foreach ($travelRequest->approval_chain as $index => $step)
                        @php
                            $approver = \App\Models\User::find($step['approver_id']);
                            $action   = $travelRequest->approvalActions->where('stage', $step['stage'])->first();
                            $isCurrent= (int)$travelRequest->current_approver_id === (int)$step['approver_id'];
                            $isDone   = $action !== null;

                            if ($isDone) {
                                $chipClass = $action->decision === 'approved'
                                    ? 'bg-green-50 border-green-300 text-green-800'
                                    : 'bg-red-50 border-red-300 text-red-800';
                            } elseif ($isCurrent) {
                                $chipClass = 'bg-yellow-50 border-yellow-400 text-yellow-800';
                            } else {
                                $chipClass = 'bg-gray-50 border-gray-200 text-gray-500';
                            }
                        @endphp

                        @if ($index > 0)
                            <span class="text-gray-300 text-lg">→</span>
                        @endif

                        <div class="border rounded-lg px-3 py-2 text-xs {{ $chipClass }}">
                            <div class="font-semibold">{{ $stageLabels[$step['stage']] ?? $step['stage'] }}</div>
                            <div class="text-xs opacity-75">{{ $approver?->name }}</div>
                            @if ($isDone)
                                <div class="text-xs font-semibold mt-0.5">
                                    {{ $action->decision === 'approved' ? '✓ Imeidhinishwa' : '✗ Imekataliwa' }}
                                    · {{ $action->acted_at->format('d/m/Y H:i') }}
                                </div>
                            @elseif ($isCurrent)
                                <div class="text-xs font-semibold mt-0.5">⏳ Inasubiri...</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ============================================================ --}}
            {{-- APPROVAL ACTION FORM (shown only to the current approver)    --}}
            {{-- ============================================================ --}}
            @if ($travelRequest->status === 'pending' && (int)$travelRequest->current_approver_id === (int)auth()->id())
            <div class="bg-white shadow-sm rounded-lg overflow-hidden border-l-4 border-blue-600">
                <div class="px-6 py-4 bg-blue-50 border-b border-blue-100">
                    <h3 class="text-sm font-bold text-blue-900">Hatua Yako ya Idhini</h3>
                    <p class="text-xs text-blue-700 mt-0.5">Tafadhali soma fomu yote kabla ya kufanya uamuzi.</p>
                </div>
                <form method="POST" action="{{ route('travel-requests.approve', $travelRequest) }}" class="p-6 space-y-4">
                    @csrf

                    @if ($errors->any())
                        <div class="p-3 bg-red-50 border border-red-200 rounded text-red-700 text-xs">
                            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Maoni / Tija na Umuhimu</label>
                        <textarea name="comment" rows="4"
                            placeholder="Andika maoni yako hapa..."
                            class="w-full border border-gray-300 rounded-md text-sm p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none">{{ old('comment') }}</textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" name="decision" value="approved"
                            onclick="return confirm('Una uhakika wa KUIDHINISHA ombi hili?')"
                            class="px-6 py-2 bg-green-700 text-white text-sm font-semibold rounded-md hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            ✓ Idhinisha (ARUHUSIWE)
                        </button>
                        <button type="submit" name="decision" value="rejected"
                            onclick="return confirm('Una uhakika wa KUKATAA ombi hili?')"
                            class="px-6 py-2 bg-red-700 text-white text-sm font-semibold rounded-md hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            ✗ Kataa (ASIRUHUSIWE)
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- ============================================================ --}}
            {{-- AUDIT TRAIL                                                   --}}
            {{-- ============================================================ --}}
            @if ($travelRequest->approvalActions->count() > 0)
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500">Historia ya Idhini</h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($travelRequest->approvalActions as $action)
                    <div class="px-6 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <span class="text-sm font-semibold text-gray-800">{{ $action->actor?->name }}</span>
                                <span class="ml-2 text-xs text-gray-500">{{ $action->actor?->job_title }}</span>
                                <span class="ml-2 px-2 py-0.5 rounded text-xs font-semibold
                                    {{ $action->decision === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $action->decision === 'approved' ? 'ARUHUSIWE' : 'ASIRUHUSIWE' }}
                                </span>
                            </div>
                            <div class="text-right text-xs text-gray-500">
                                <div>{{ $action->stageLabel() }}</div>
                                <div class="font-medium text-gray-700">{{ $action->acted_at->format('d M Y, H:i') }}</div>
                            </div>
                        </div>
                        @if ($action->comment)
                            <div class="mt-2 text-sm text-gray-700 bg-gray-50 rounded p-3 border border-gray-100">
                                {{ $action->comment }}
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- ============================================================ --}}
            {{-- THE FORM — READ ONLY                                          --}}
            {{-- ============================================================ --}}
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">

                {{-- Document Header --}}
                <div class="text-center p-6 border-b-2 border-gray-800">
                    <p class="text-right text-xs italic font-semibold text-gray-600">Fomu Na: NIMR – ADM – F002</p>
                    <h1 class="text-sm font-bold uppercase tracking-wide mt-1">Taasisi ya Taifa ya Utafiti wa Magonjwa ya Binadamu</h1>
                    <div class="flex justify-center my-3">
                        <img src="{{ asset('NIMR.png') }}" alt="NIMR" class="h-16 w-16 object-contain">
                    </div>
                    <h2 class="text-sm font-bold uppercase tracking-wide">Fomu ya Maombi ya Ruhusa ya Kusafiri Ndani ya Nchi</h2>
                </div>

                <div class="p-6 space-y-8">

                    @php
                        $ro = 'block w-full text-sm text-gray-800 border-b border-gray-300 py-0.5 px-1 bg-transparent';
                    @endphp

                    {{-- ================================================ --}}
                    {{-- SECTION A --}}
                    {{-- ================================================ --}}
                    <div>
                        <h3 class="font-bold text-sm uppercase border-b-2 border-gray-800 pb-1 mb-3">A: Utangulizi:</h3>
                        <div class="text-xs space-y-2 text-gray-700 leading-relaxed">
                            <p><span class="font-semibold">(i)</span> Watumishi wote wanaotarajia kusafiri ndani ya nchi wanapaswa kujaza kikamilifu fomu hii siku 14 kabla ya safari.</p>
                            <p><span class="font-semibold">(ii)</span> Pamoja na fomu hii, viambatisho muhimu vinavyothibitisha safari lazima viwepo (mfano barua ya mwaliko, maelezo ya wazi ya gharama zote za safari na kwamba ni nani ana jukumu la kuzilipa, kama zinalipwa kwa sehemu na mfadhili, ni kiasi gani kinatakiwa kugharamiwa na Taasisi.</p>
                            <p><span class="font-semibold">(iii)</span> Fomu hii itajazwa na kila Mtumishi wa Taasisi atakayesafiri kwa shughuli za kiofisi ndani ya nchi.</p>
                            <p><span class="font-semibold">(iv)</span> Fomu ambayo haikujazwa kikamilifu kuonesha taarifa zote muhimu na kwa kiwango kinachoeleweka au imechelewa chini ya siku zilizotajwa hapo juu haitashughulikiwa na hivyo Mtumishi atakuwa amejinyima ruhusa mwenyewe ya kusafiri.</p>
                            <p><span class="font-semibold">(v)</span> Ndani ya kipindi cha wiki mbili baada ya kurudi kutoka safarini Mtumishi anatakiwa kuwasilisha ripoti ya safari kwa Mkurugenzi Mkuu kupitia kwa Mkurugenzi wa Idara/ Meneja wa Kituo au Mkuu wa Idara anapofanyia kazi. Nakala ya ripoti hii pia iwasilishwe Ofisi ya Rasilimali Watu na Utawala.</p>
                            <p><span class="font-semibold">(vi)</span> Fomu hii ikishajazwa kikamilifu na kibali kutolewa na mwenye Mamlaka, nakala moja ya fomu hii ikiwa imeshawekwa mhuri wa Mkurugenzi Mkuu au Mkurugenzi wa Kituo irudishwe Ofisi ya Rasilimali Watu na Utawala.</p>
                            <p><span class="font-semibold">(vii)</span> Safari za dharura zitatumia utaratibu wa dharura.</p>
                            <p><span class="font-semibold">(viii)</span> Fomu hii itajazwa nakala mbili (2).</p>
                        </div>
                    </div>

                    {{-- ================================================ --}}
                    {{-- SECTION B --}}
                    {{-- ================================================ --}}
                    <div>
                        <h3 class="font-bold text-sm uppercase border-b-2 border-gray-800 pb-1 mb-4">B: Taarifa za Mtumishi Anayesafiri</h3>
                        <div class="space-y-3">
                            @php
                                $tr = $travelRequest;
                                $labelClass = 'text-sm w-72 shrink-0 text-gray-600';
                                $numClass   = 'text-sm w-8 shrink-0 text-gray-500';
                            @endphp

                            @foreach ([
                                ['(i)',   'Jina la Mtumishi anayesafiri', $tr->b_applicant_name],
                                ['(ii)',  'Simu',                         $tr->b_phone],
                                ['(iii)', 'Barua Pepe',                   $tr->b_email],
                                ['(iv)',  'Cheo',                         $tr->b_position],
                                ['(v)',   'Mikoa/Mkoa/Wilaya anapokwenda',$tr->b_destination],
                                ['(vi)',  'Tarehe ya Kuondoka',           $tr->b_departure_date?->format('d M Y')],
                                ['(vii)', 'Tarehe ya Kurudi',             $tr->b_return_date?->format('d M Y')],
                            ] as [$num, $label, $value])
                            <div class="flex items-baseline gap-2">
                                <span class="{{ $numClass }}">{{ $num }}</span>
                                <span class="{{ $labelClass }}">{{ $label }}</span>
                                <span class="{{ $ro }}">{{ $value ?? '—' }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ================================================ --}}
                    {{-- SECTION C --}}
                    {{-- ================================================ --}}
                    <div>
                        <h3 class="font-bold text-sm uppercase border-b-2 border-gray-800 pb-1 mb-2">C: Chanzo cha Safari (Kielezwe kwa Kina):</h3>
                        <p class="text-xs text-gray-600 mb-2">Ni nani aliyeanzisha safari? Je ni mtumishi? Je ni Serikali au Mwaliko mwingine?</p>
                        <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[4rem]">{{ $travelRequest->c_travel_source ?? '—' }}</div>
                    </div>

                    {{-- ================================================ --}}
                    {{-- SECTION D --}}
                    {{-- ================================================ --}}
                    <div>
                        <h3 class="font-bold text-sm uppercase border-b-2 border-gray-800 pb-1 mb-3">D: Faida ya Safari na Athari Zitakazokuwepo kama Safari Haikupitishwa.</h3>

                        <p class="text-sm mb-3">(i) Nini Faida / Tija na Umuhimu wa safari husika kwa Taasisi na Taifa kwa ujumla:</p>

                        <div class="ml-6 mb-4">
                            <p class="text-sm mb-1">(a) Tija na Umuhimu kwa Taasisi:</p>
                            <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[4rem]">{{ $travelRequest->d_benefit_to_institution ?? '—' }}</div>
                        </div>

                        <div class="ml-6 mb-4">
                            <p class="text-sm mb-1">(b) Tija na Umuhimu kwa Taifa:</p>
                            <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[4rem]">{{ $travelRequest->d_benefit_to_nation ?? '—' }}</div>
                        </div>

                        <div>
                            <p class="text-sm mb-1">(i) Athari zitakazokuwepo kama safari haikupitishwa:</p>
                            <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[4rem]">{{ $travelRequest->d_consequences_if_rejected ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- ================================================ --}}
                    {{-- SECTION E --}}
                    {{-- ================================================ --}}
                    <div>
                        <h3 class="font-bold text-sm uppercase border-b-2 border-gray-800 pb-1 mb-4">E: Gharama za Safari Zikianisha Yafuatayo:</h3>

                        <div class="mb-4">
                            <p class="text-sm mb-1">(i) Gharama za Usafiri <strong>(Taja kiwango)</strong></p>
                            <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[3rem]">{{ $travelRequest->e_transport_costs ?? '—' }}</div>
                        </div>

                        <div class="mb-4">
                            <p class="text-sm mb-2">(ii) Posho zote za safari husika: <strong>(Taja kiwango)</strong></p>
                            <div class="ml-6 mb-3">
                                <p class="text-sm mb-2">a. Posho zote za safari husika:</p>
                                <div class="space-y-2 ml-4">
                                    @foreach (['a' => $travelRequest->e_allowance_a, 'b' => $travelRequest->e_allowance_b, 'c' => $travelRequest->e_allowance_c, 'd' => $travelRequest->e_allowance_d] as $letter => $val)
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-sm w-8 shrink-0 text-gray-500">({{ $letter }})</span>
                                        <span class="{{ $ro }}">{{ $val ?? '—' }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ml-6 flex items-baseline gap-2">
                                <span class="text-sm shrink-0">b. Kifungu cha Safari:</span>
                                <span class="{{ $ro }}">{{ $travelRequest->e_budget_line ?? '—' }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="text-sm mb-2">(ii) Eleza mlipaji wa gharama za safari husika:</p>
                            <div class="ml-6 mb-3">
                                <p class="text-sm mb-2">(a) Gharama zinazolipwa na Wafadhili:</p>
                                <div class="space-y-2 ml-4">
                                    @foreach (['i' => $travelRequest->e_donor_cost_i, 'ii' => $travelRequest->e_donor_cost_ii, 'iii' => $travelRequest->e_donor_cost_iii] as $num => $val)
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-sm w-10 shrink-0 text-gray-500">({{ $num }})</span>
                                        <span class="{{ $ro }}">{{ $val ?? '—' }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="ml-6">
                                <p class="text-sm mb-2">(b) Gharama zinazolipwa na Serikali kama zipo:</p>
                                <div class="space-y-2 ml-4">
                                    @foreach (['i' => $travelRequest->e_govt_cost_i, 'ii' => $travelRequest->e_govt_cost_ii, 'iii' => $travelRequest->e_govt_cost_iii] as $num => $val)
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-sm w-10 shrink-0 text-gray-500">({{ $num }})</span>
                                        <span class="{{ $ro }}">{{ $val ?? '—' }}</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm mb-1">(iv) Gharama zingine nje ya zilizotajwa hapo juu:</p>
                            <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[3rem]">{{ $travelRequest->e_other_costs ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- ================================================ --}}
                    {{-- SECTION F --}}
                    {{-- ================================================ --}}
                    <div>
                        <h3 class="font-bold text-sm uppercase border-b-2 border-gray-800 pb-1 mb-2">F: Manufaa Yaliyopatikana kwa Taasisi na Taifa kwa Safari kama Hiyo kwa Kipindi cha Nyuma: <span class="font-normal normal-case">(Impact Assessment of the Previous Travels).</span></h3>
                        <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[4rem] mb-4">{{ $travelRequest->f_previous_travel_impact ?? '—' }}</div>

                    </div>

                    {{-- ================================================ --}}
                    {{-- SECTION G --}}
                    {{-- ================================================ --}}
                    <div>
                        <h3 class="font-bold text-sm uppercase border-b-2 border-gray-800 pb-1 mb-3">G: Taja Jina la Afisa na Cheo cha Utakayemkaimisha Majukumu Yako. <span class="font-normal normal-case">(Handover Note)</span></h3>
                        <div class="space-y-3">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm w-20 shrink-0 text-gray-600">Jina:</span>
                                <span class="{{ $ro }}">{{ $travelRequest->g_handover_officer_name ?? '—' }}</span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm w-20 shrink-0 text-gray-600">Cheo:</span>
                                <span class="{{ $ro }}">{{ $travelRequest->g_handover_officer_title ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- ================================================ --}}
                    {{-- SECTIONS H/I/J/K — approval signatures (read-only) --}}
                    {{-- ================================================ --}}
                    @if ($travelRequest->approvalActions->count() > 0)
                    <div class="border-t-2 border-gray-800 pt-6 space-y-6">

                        @foreach ($travelRequest->approvalActions as $action)
                        <div>
                            @php
                                $sectionLabels = [
                                    'supervisor' => 'H: Maoni ya Mkurugenzi / Meneja / Mkuu wa Idara Kuhusu Tija na Umuhimu wa Safari kwa Taasisi na Taifa.',
                                    'director'   => 'I: Maoni ya Mkuu wa Idara / Kituo au Mkurugenzi Kuhusu Tija na Umuhimu wa Safari kwa Taasisi na Taifa.',
                                    'final'      => 'J: Kibali cha Kusafiri Ndani ya Tanzania (Itajazwa na Meneja wa Kituo au Mkurugenzi Mkuu).',
                                    'hr'         => 'K: Kwa Taarifa — Meneja Rasilimali Watu na Utawala / Afisa Rasilimali Watu / Afisa Utawala.',
                                ];
                            @endphp
                            <h3 class="font-bold text-sm uppercase border-b border-gray-400 pb-1 mb-3">{{ $sectionLabels[$action->stage] ?? $action->stage }}</h3>

                            @if ($action->stage !== 'hr')
                            <div class="mb-2">
                                <p class="text-sm mb-1">(i) Tija na Umuhimu:</p>
                                <div class="text-sm text-gray-800 bg-gray-50 rounded p-3 border border-gray-200 whitespace-pre-wrap min-h-[3rem]">
                                    {{ $action->comment ?? '—' }}
                                </div>
                            </div>
                            @endif

                            <div class="flex flex-wrap gap-8 mt-3">
                                @if ($action->stage === 'final')
                                <div>
                                    <span class="text-sm">Maombi ya kusafiri ndani ya Tanzania kwa mtumishi alietajwa hapo juu:</span><br>
                                    <span class="text-sm font-bold {{ $action->decision === 'approved' ? 'text-green-700' : 'text-red-700' }}">
                                        {{ $action->decision === 'approved' ? 'YAMEKUBALIWA' : 'YAMEKATALIWA' }}
                                    </span>
                                </div>
                                @else
                                <div>
                                    <span class="text-sm font-semibold {{ $action->decision === 'approved' ? 'text-green-700' : 'text-red-700' }}">
                                        {{ $action->decision === 'approved' ? 'ARUHUSIWE' : 'ASIRUHUSIWE' }}
                                    </span>
                                </div>
                                @endif

                                <div class="text-sm text-gray-500">
                                    {{ $action->acted_at->format('d M Y, H:i') }}
                                </div>
                            </div>

                            <div class="mt-2 text-sm">
                                Jina: <span class="font-medium">{{ $action->actor?->name }}</span>
                                &nbsp;&nbsp; Cheo: <span class="font-medium">{{ $action->actor?->job_title }}</span>
                            </div>
                        </div>
                        @endforeach

                    </div>
                    @endif

                </div>{{-- end p-6 --}}
            </div>{{-- end form card --}}

        </div>
    </div>
</x-app-layout>
