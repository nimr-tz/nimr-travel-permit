@php
    $roleLabels = [
        'staff'            => 'Mtumishi (Staff)',
        'head'             => 'Mkuu wa Kitengo (Head of Section)',
        'manager'          => 'Meneja (Manager)',
        'director'         => 'Mkurugenzi (Director)',
        'centre_manager'   => 'Meneja wa Kituo (Centre Manager)',
        'director_general' => 'Mkurugenzi Mkuu (Director General)',
        'hr'               => 'Rasilimali Watu (HR)',
    ];
    $unitTypeLabels = [
        'hq_standalone'  => 'HQ — Kitengo huru',
        'hq_directorate' => 'HQ — Idara',
        'hq_section'     => 'HQ — Sehemu',
        'research_centre'=> 'Kituo cha Utafiti',
    ];
    $inputClass = 'w-full border border-gray-300 rounded-md text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
    $labelClass = 'block text-sm font-medium text-gray-700 mb-1';
@endphp

@if ($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-300 rounded-lg text-red-700 text-sm">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="bg-white shadow-sm rounded-lg p-6 space-y-5">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <div>
            <label class="{{ $labelClass }}">Jina Kamili *</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="{{ $inputClass }}" required>
        </div>

        <div>
            <label class="{{ $labelClass }}">Barua Pepe *</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="{{ $inputClass }}" required>
        </div>

        <div>
            <label class="{{ $labelClass }}">Nambari ya Utumishi</label>
            <input type="text" name="staff_number" value="{{ old('staff_number', $user->staff_number ?? '') }}" class="{{ $inputClass }}">
        </div>

        <div>
            <label class="{{ $labelClass }}">Simu</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="{{ $inputClass }}">
        </div>

        <div>
            <label class="{{ $labelClass }}">Cheo / Job Title</label>
            <input type="text" name="job_title" value="{{ old('job_title', $user->job_title ?? '') }}" class="{{ $inputClass }}">
        </div>

        <div>
            <label class="{{ $labelClass }}">Nenosiri {{ isset($user) ? '(acha wazi ikiwa haubadilishi)' : '*' }}</label>
            <input type="password" name="password" class="{{ $inputClass }}" {{ isset($user) ? '' : 'required' }}>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <div>
            <label class="{{ $labelClass }}">Kitengo / Unit *</label>
            <select name="unit_id" class="{{ $inputClass }}">
                <option value="">— Chagua kitengo —</option>
                @foreach ($units->groupBy('type') as $type => $group)
                    <optgroup label="{{ $unitTypeLabels[$type] ?? $type }}">
                        @foreach ($group as $unit)
                        <option value="{{ $unit->id }}"
                            {{ old('unit_id', $user->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $labelClass }}">Wadhifu / Role *</label>
            <select name="role" class="{{ $inputClass }}" required>
                @foreach ($roleLabels as $key => $label)
                <option value="{{ $key }}" {{ old('role', $user->role ?? 'staff') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $labelClass }}">Msimamizi (kwa watumishi wa vituo)</label>
            <select name="supervisor_id" class="{{ $inputClass }}">
                <option value="">— Hakuna msimamizi —</option>
                @foreach ($supervisors as $sup)
                <option value="{{ $sup->id }}"
                    {{ old('supervisor_id', $user->supervisor_id ?? '') == $sup->id ? 'selected' : '' }}>
                    {{ $sup->name }} ({{ $sup->job_title }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3 pt-6">
            <input type="checkbox" name="is_active" id="is_active" value="1"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
            <label for="is_active" class="text-sm font-medium text-gray-700">Akaunti inaendelea (Active)</label>
        </div>

    </div>

</div>
