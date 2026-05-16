@php
    $roleLabels = [];
    foreach (['staff','head','manager','director','centre_manager','director_general','hr'] as $role) {
        $roleLabels[$role] = __('common.role_' . $role);
    }
    $unitTypeLabels = [];
    foreach (['hq_standalone','hq_directorate','hq_section','research_centre'] as $type) {
        $unitTypeLabels[$type] = __('common.unit_' . $type);
    }
@endphp

@if ($errors->any())
<div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
    <div class="flex items-center gap-2 mb-2 font-semibold">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ __('users.errors_title') }}
    </div>
    <ul class="list-disc list-inside space-y-0.5">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="card p-6 space-y-6">

    <div>
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">{{ __('users.form_personal') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="field">
                <label class="label">{{ __('users.field_name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="input @error('name') input-error @enderror" required placeholder="{{ __('users.field_name_ph') }}">
            </div>
            <div class="field">
                <label class="label">{{ __('users.field_email') }} <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="input @error('email') input-error @enderror" required placeholder="jina@nimr.or.tz">
            </div>
            <div class="field">
                <label class="label">{{ __('users.field_staff_no') }}</label>
                <input type="text" name="staff_number" value="{{ old('staff_number', $user->staff_number ?? '') }}" class="input @error('staff_number') input-error @enderror" placeholder="NIMR/HQ/001">
            </div>
            <div class="field">
                <label class="label">{{ __('users.field_phone') }}</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="input @error('phone') input-error @enderror" placeholder="+255 7XX XXX XXX">
            </div>
            <div class="field md:col-span-2">
                <label class="label">{{ __('users.field_job_title') }}</label>
                <input type="text" name="job_title" value="{{ old('job_title', $user->job_title ?? '') }}" class="input @error('job_title') input-error @enderror" placeholder="Research Officer, Senior Scientist, ...">
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-5">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">{{ __('users.form_org') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="field">
                <label class="label">{{ __('users.field_unit') }} <span class="text-red-500">*</span></label>
                <select name="unit_id" class="select @error('unit_id') input-error @enderror">
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
                <select name="role" class="select @error('role') input-error @enderror" required>
                    @foreach ($roleLabels as $key => $label)
                    <option value="{{ $key }}" {{ old('role', $user->role ?? 'staff') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="label">{{ __('users.field_supervisor') }}</label>
                <select name="supervisor_id" class="select @error('supervisor_id') input-error @enderror">
                    <option value="">{{ __('users.field_supervisor_ph') }}</option>
                    @foreach ($supervisors as $sup)
                    <option value="{{ $sup->id }}" {{ old('supervisor_id', $user->supervisor_id ?? '') == $sup->id ? 'selected' : '' }}>
                        {{ $sup->name }}{{ $sup->job_title ? ' — ' . $sup->job_title : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3 pt-1 self-end pb-0.5">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer"
                    {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm font-medium text-slate-700 cursor-pointer select-none">
                    {{ __('users.field_is_active') }}
                </label>
            </div>
        </div>
    </div>

    <div class="border-t border-slate-100 pt-5">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">{{ __('users.form_security') }}</h3>
        <div class="field max-w-sm">
            <label class="label">
                {{ __('users.field_password') }}
                @isset($user)<span class="font-normal text-slate-400 ml-1">{{ __('users.field_password_hint') }}</span>@endisset
                @unless(isset($user))<span class="text-red-500">*</span>@endunless
            </label>
            <input type="password" name="password" class="input @error('password') input-error @enderror"
                {{ isset($user) ? '' : 'required' }}
                placeholder="{{ isset($user) ? '••••••••' : __('users.field_password_ph') }}"
                autocomplete="new-password">
        </div>
    </div>

</div>
