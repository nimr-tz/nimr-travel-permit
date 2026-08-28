@php
    $roleLabels = [];
    foreach ($roles as $role) {
        $roleLabels[$role] = __('common.role_' . $role);
    }
    $unitTypeLabels = [];
    foreach (['hq_standalone','hq_directorate','hq_section','research_centre'] as $type) {
        $unitTypeLabels[$type] = __('common.unit_' . $type);
    }
    $currentUnitId = (string) old('unit_id', $user->unit_id ?? '');
    $currentRole = (string) old('role', $user->role ?? 'staff');
    $currentSupervisorId = (string) old('supervisor_id', $user->supervisor_id ?? '');
    $unitSupervisorMeta = $units->mapWithKeys(fn ($unit) => [
        (string) $unit->id => ['type' => $unit->type, 'parent_id' => $unit->parent_id ? (string) $unit->parent_id : null],
    ])->all();
    $supervisorOptionsData = collect($supervisorOptions ?? [])->map(fn ($supervisor) => [
        'id' => (string) $supervisor->id,
        'unit_id' => (string) $supervisor->unit_id,
        'role' => $supervisor->role,
        'name' => $supervisor->name,
        'title' => $supervisor->job_title ?: __('common.role_' . $supervisor->role),
        'unit' => $supervisor->unit?->name,
    ])->values();
@endphp

@if ($errors->any())
<div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm">
    <div class="flex items-center gap-2 mb-2 font-semibold">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ __('users.errors_title') }}
    </div>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

{{-- Personal information --}}
<section class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('users.form_personal') }}</h3>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="field">
            <label class="label">{{ __('users.field_name') }} <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
                   class="input @error('name') input-error @enderror"
                   required placeholder="{{ __('users.field_name_ph') }}">
        </div>
        <div class="field">
            <label class="label">{{ __('users.field_email') }} <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                   class="input @error('email') input-error @enderror"
                   required placeholder="jina@nimr.or.tz">
        </div>
        <div class="field">
            <label class="label">{{ __('users.field_phone') }}</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                   class="input @error('phone') input-error @enderror"
                   placeholder="+255 7XX XXX XXX">
        </div>
        <div class="field md:col-span-2">
            <label class="label">{{ __('users.field_job_title') }}</label>
            <input type="text" name="job_title" value="{{ old('job_title', $user->job_title ?? '') }}"
                   class="input @error('job_title') input-error @enderror"
                   placeholder="Research Officer, Senior Scientist…">
        </div>
    </div>
</section>

{{-- Organisational structure --}}
<section class="rounded-xl border border-slate-200 bg-white shadow-sm"
         x-data="{
            selectedUnitId: {{ Js::from($currentUnitId) }},
            selectedRole: {{ Js::from($currentRole) }},
            selectedSupervisorId: {{ Js::from($currentSupervisorId) }},
            units: {{ Js::from($unitSupervisorMeta) }},
            supervisors: {{ Js::from($supervisorOptionsData) }},
            supervisorRole() {
                const unit = this.units[this.selectedUnitId];
                if (!unit) return null;
                if (unit.type === 'research_centre' && ['staff', 'manager', 'hr', 'system_admin'].includes(this.selectedRole)) return 'manager';
                if (unit.type === 'hq_section' && ['staff', 'hr', 'system_admin'].includes(this.selectedRole)) return 'head_or_manager';
                if (unit.type === 'hq_standalone' && ['staff', 'hr', 'system_admin'].includes(this.selectedRole)) return 'manager';
                return null;
            },
            fixedSupervisor() {
                const unit = this.units[this.selectedUnitId];
                if (!unit) return null;

                const reportsToDg =
                    (unit.type === 'research_centre' && this.selectedRole === 'centre_manager') ||
                    (unit.type === 'hq_standalone' && this.selectedRole === 'manager') ||
                    (unit.type === 'hq_directorate' && this.selectedRole === 'director');

                if (reportsToDg) {
                    return this.supervisors.find((supervisor) => supervisor.role === 'director_general') || null;
                }

                // Section lead (head of a scientific section, or manager of a Corporate
                // Services section) reports straight to their Directorate's Director.
                const reportsToDirectorate = unit.type === 'hq_section' && ['head', 'manager'].includes(this.selectedRole);

                if (reportsToDirectorate && unit.parent_id) {
                    return this.supervisors.find((supervisor) => supervisor.unit_id === unit.parent_id && supervisor.role === 'director') || null;
                }

                return null;
            },
            fixedSupervisorLabel() {
                const fixed = this.fixedSupervisor();
                return fixed ? `${fixed.name} - ${fixed.title}` : '';
            },
            filteredSupervisors() {
                const fixed = this.fixedSupervisor();
                if (fixed) return [fixed];

                const role = this.supervisorRole();
                if (!role) return [];
                // A section's lead is either a "head" (scientific sections) or a
                // "manager" (Corporate Services sections) — match whichever leads it.
                const roles = role === 'head_or_manager' ? ['head', 'manager'] : [role];
                return this.supervisors.filter((supervisor) => supervisor.unit_id === String(this.selectedUnitId) && roles.includes(supervisor.role));
            },
            supervisorApplies() {
                return this.fixedSupervisor() !== null || this.supervisorRole() !== null;
            },
            syncSupervisor() {
                const fixed = this.fixedSupervisor();
                if (fixed) {
                    this.selectedSupervisorId = fixed.id;
                    return;
                }

                if (!this.filteredSupervisors().some((supervisor) => supervisor.id === String(this.selectedSupervisorId))) {
                    this.selectedSupervisorId = '';
                }
            },
         }"
         x-init="$watch('selectedUnitId', () => syncSupervisor()); $watch('selectedRole', () => syncSupervisor()); syncSupervisor();">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('users.form_org') }}</h3>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="field">
            <label class="label">{{ __('users.field_unit') }} <span class="text-red-500">*</span></label>
            <select name="unit_id" x-model="selectedUnitId" class="select @error('unit_id') input-error @enderror">
                <option value="">{{ __('users.field_unit_ph') }}</option>
                @foreach ($units->groupBy('type') as $type => $group)
                <optgroup label="{{ $unitTypeLabels[$type] ?? $type }}">
                    @foreach ($group as $unit)
                    <option value="{{ $unit->id }}" {{ old('unit_id', $user->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->name }}
                    </option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label class="label">{{ __('users.field_role') }} <span class="text-red-500">*</span></label>
            <select name="role" x-model="selectedRole" class="select @error('role') input-error @enderror" required>
                @foreach ($roleLabels as $key => $label)
                <option value="{{ $key }}" {{ old('role', $user->role ?? 'staff') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="field md:col-span-2" x-show="supervisorApplies()" x-transition>
            <label class="label">{{ __('users.field_supervisor') }}</label>
            <input type="hidden"
                   name="supervisor_id"
                   :value="fixedSupervisor() ? fixedSupervisor().id : ''"
                   :disabled="fixedSupervisor() === null">
            <input type="text"
                   class="input bg-slate-50 text-slate-600"
                   :value="fixedSupervisorLabel()"
                   x-show="fixedSupervisor() !== null"
                   readonly>
            <select name="supervisor_id"
                    x-model="selectedSupervisorId"
                    x-show="fixedSupervisor() === null"
                    :disabled="fixedSupervisor() !== null"
                    class="select @error('supervisor_id') input-error @enderror disabled:bg-slate-50 disabled:text-slate-500">
                <option value="">{{ __('users.field_supervisor_ph') }}</option>
                <template x-for="supervisor in filteredSupervisors()" :key="supervisor.id">
                    <option :value="supervisor.id" x-text="`${supervisor.name} - ${supervisor.title}`"></option>
                </template>
            </select>
            <p class="mt-1 text-xs text-slate-500" x-show="fixedSupervisor() !== null">
                {{ __('users.field_supervisor_auto') }}
            </p>
            <p class="mt-1 text-xs text-slate-400" x-show="filteredSupervisors().length === 0">
                {{ __('users.field_supervisor_empty') }}
            </p>
            @error('supervisor_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex items-center gap-3 pt-1 self-end pb-0.5 md:col-span-2">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                   class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer"
                   {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="text-sm font-medium text-slate-700 cursor-pointer select-none">
                {{ __('users.field_is_active') }}
            </label>
        </div>
        @error('is_active')
        <p class="text-xs text-red-600 md:col-span-2">{{ $message }}</p>
        @enderror
    </div>
</section>

{{-- Security --}}
<section class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="px-6 py-4 border-b border-slate-100">
        <h3 class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ __('users.form_security') }}</h3>
    </div>
    <div class="p-6">
        @if (isset($user))
        <div class="field max-w-sm">
            <label class="label">
                {{ __('users.field_password') }}
                <span class="font-normal text-slate-400 ml-1 text-sm">{{ __('users.field_password_hint') }}</span>
            </label>
            <input type="password" name="password"
                   class="input @error('password') input-error @enderror"
                   placeholder="••••••••" autocomplete="new-password">
        </div>
        @else
        <div class="flex items-start gap-3 p-4 rounded-xl bg-blue-50 border border-blue-100 text-sm text-blue-700 max-w-lg">
            <svg class="w-5 h-5 shrink-0 mt-0.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <p>{{ __('users.invite_notice') }}</p>
        </div>
        @endif
    </div>
</section>
