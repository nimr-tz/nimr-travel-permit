<x-guest-layout>
    <div x-data="registrationWizard()" x-init="init()" class="space-y-5">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Create Your Account</h2>
            <p class="text-sm text-slate-500 mt-1">Use your NIMR email and select where you work.</p>
        </div>

        <div class="flex items-center justify-center gap-2">
            <template x-for="(step, index) in steps" :key="step">
                <div class="flex items-center">
                    <div class="flex flex-col items-center">
                        <div class="h-8 w-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition"
                            :class="index < currentStep ? 'bg-emerald-500 border-emerald-500 text-white' : (index === currentStep ? 'border-[#05499c] text-[#05499c]' : 'border-slate-200 text-slate-400')">
                            <template x-if="index < currentStep">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </template>
                            <template x-if="index >= currentStep">
                                <span x-text="index + 1"></span>
                            </template>
                        </div>
                        <span class="text-[10px] mt-1 font-semibold" :class="index === currentStep ? 'text-[#05499c]' : 'text-slate-400'" x-text="step"></span>
                    </div>
                    <div x-show="index < steps.length - 1" class="h-0.5 w-8 mx-1" :class="index < currentStep ? 'bg-emerald-400' : 'bg-slate-200'"></div>
                </div>
            </template>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold mb-1">Please check the details below.</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" @submit="handleSubmit">
            @csrf
            <input type="hidden" name="organizational_level" x-model="formData.organizational_level">
            <input type="hidden" name="unit_id" x-model="formData.unit_id">

            <div class="pb-2">
                <div x-show="currentStep === 0" x-cloak class="space-y-4">
                    <div>
                        <x-input-label for="name" value="Full Name" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" x-model="formData.name" required autofocus autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="NIMR Email" />
                        <div class="relative mt-1">
                            <x-text-input id="email" class="block w-full pr-10" type="email" name="email" x-model="formData.email" required autocomplete="username" placeholder="your.name@nimr.or.tz" />
                            <div class="absolute inset-y-0 right-3 flex items-center">
                                <svg x-show="formData.email && emailIsValid()" class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <svg x-show="formData.email && !emailIsValid()" class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs mt-1" :class="formData.email && !emailIsValid() ? 'text-red-500' : 'text-slate-400'">
                            Official {{ '@' . config('app.allowed_email_domain', 'nimr.or.tz') }} email required.
                        </p>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="phone" value="Phone Number" />
                            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" x-model="formData.phone" placeholder="+255 7XX XXX XXX" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="job_title" value="Job Title" />
                            <x-text-input id="job_title" class="block mt-1 w-full" type="text" name="job_title" x-model="formData.job_title" />
                            <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div x-show="currentStep === 1" x-cloak class="space-y-4">
                    <div>
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" x-model="formData.password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                        <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" x-model="formData.password_confirmation" required autocomplete="new-password" />
                        <p class="text-xs text-slate-400 mt-1">Use at least 8 characters.</p>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <div x-show="currentStep === 2" x-cloak class="space-y-4">
                    <div>
                        <x-input-label for="organizational_level" value="Where do you work?" />
                        <select id="organizational_level" x-model="formData.organizational_level" @change="clearUnit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select workplace type</option>
                            <option value="headquarters">Headquarters</option>
                            <option value="research_centre">Research Centre</option>
                        </select>
                        <x-input-error :messages="$errors->get('organizational_level')" class="mt-2" />
                    </div>

                    <div x-show="formData.organizational_level === 'headquarters'" x-transition>
                        <x-input-label for="hq_unit_id" value="Headquarters Unit" />
                        <select id="hq_unit_id" x-model="formData.unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select your HQ unit</option>
                            @if ($hqStandaloneUnits->isNotEmpty())
                                <optgroup label="Standalone Units">
                                    @foreach ($hqStandaloneUnits as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @foreach ($hqDirectorates as $directorate)
                                <optgroup label="{{ $directorate->name }}">
                                    <option value="{{ $directorate->id }}">{{ $directorate->name }}</option>
                                    @foreach ($directorate->children as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                    </div>

                    <div x-show="formData.organizational_level === 'research_centre'" x-transition>
                        <x-input-label for="centre_unit_id" value="Research Centre" />
                        <select id="centre_unit_id" x-model="formData.unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select your centre</option>
                            @foreach ($researchCentres as $centre)
                                <option value="{{ $centre->id }}">{{ $centre->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Kitengo ulichochagua kinatumika kupeleka maombi ya safari kwa waidhinishaji. Unaweza kuomba Msimamizi wa Mfumo kubadilisha kitengo chako baadaye iwapo utahamishiwa.
                    </div>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between gap-4 border-t border-slate-200 pt-4">
                <button type="button" @click="prevStep()" x-show="currentStep > 0" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back
                </button>
                <a x-show="currentStep === 0" class="text-sm text-slate-500 hover:text-slate-700" href="{{ route('login') }}">
                    Already registered?
                </a>

                <button type="button" @click="nextStep()" x-show="currentStep < steps.length - 1" class="ml-auto inline-flex items-center rounded-md bg-[#05499c] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Next
                </button>

                <button type="submit" x-show="currentStep === steps.length - 1" class="ml-auto inline-flex items-center rounded-md bg-[#05499c] px-5 py-2 text-sm font-semibold text-white hover:opacity-90">
                    Create Account
                </button>
            </div>
        </form>
    </div>

    <script>
        function registrationWizard() {
            return {
                currentStep: 0,
                steps: ['Profile', 'Password', 'Unit'],
                requiredDomain: @json(config('app.allowed_email_domain', 'nimr.or.tz')),
                formData: {
                    name: @json(old('name', '')),
                    email: @json(old('email', '')),
                    phone: @json(old('phone', '')),
                    job_title: @json(old('job_title', '')),
                    password: '',
                    password_confirmation: '',
                    organizational_level: @json(old('organizational_level', '')),
                    unit_id: @json((string) old('unit_id', '')),
                },
                init() {
                    @if ($errors->any())
                        @if ($errors->has('password') || $errors->has('password_confirmation'))
                            this.currentStep = 1;
                        @elseif ($errors->has('organizational_level') || $errors->has('unit_id'))
                            this.currentStep = 2;
                        @endif
                    @endif
                },
                emailIsValid() {
                    return this.formData.email.toLowerCase().endsWith('@' + this.requiredDomain.toLowerCase());
                },
                clearUnit() {
                    this.formData.unit_id = '';
                },
                nextStep() {
                    if (this.validateStep()) {
                        this.currentStep++;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                prevStep() {
                    if (this.currentStep > 0) {
                        this.currentStep--;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                validateStep() {
                    if (this.currentStep === 0) {
                        return this.formData.name && this.emailIsValid();
                    }

                    if (this.currentStep === 1) {
                        return this.formData.password.length >= 8 && this.formData.password === this.formData.password_confirmation;
                    }

                    return this.formData.organizational_level && this.formData.unit_id;
                },
                handleSubmit(event) {
                    if (!this.validateStep()) {
                        event.preventDefault();
                    }
                },
            };
        }
    </script>
</x-guest-layout>
