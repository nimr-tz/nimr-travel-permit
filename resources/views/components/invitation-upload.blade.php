@props(['travelRequest' => null])

@php
    $existing = $travelRequest?->c_invitation_document;
    $accept = ['application/pdf', 'image/jpeg', 'image/png'];
@endphp

<div {{ $attributes->merge(['class' => 'field']) }}
     x-data="{ fileName: '', dragOver: false, fileError: '', accept: @js($accept) }">

    <label class="label">
        {{ __('travel.c_invitation_upload') }}
        <span class="font-normal text-slate-400">{{ __('travel.c_invitation_hint') }}</span>
        @if ($existing)
            <span class="font-normal text-emerald-600 ml-1">{{ __('travel.g_existing_file') }}</span>
        @endif
    </label>

    <label
        :class="dragOver ? 'border-indigo-400 bg-indigo-50' : (fileName ? 'border-emerald-400 bg-emerald-50' : (fileError ? 'border-red-400 bg-red-50' : 'border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/40'))"
        class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed rounded-xl cursor-pointer transition-all"
        @dragover.prevent="dragOver = true"
        @dragleave="dragOver = false"
        @drop.prevent="
            dragOver = false;
            const f = $event.dataTransfer.files[0];
            if (!f) { return; }
            if (!accept.includes(f.type)) {
                fileError = @js(__('travel.c_invitation_bad_type')); fileName = ''; $refs.invitationInput.value = '';
                return;
            }
            const dt = new DataTransfer();
            dt.items.add(f);
            $refs.invitationInput.files = dt.files;
            fileError = ''; fileName = f.name;
        ">
        <div x-show="!fileName && !fileError" class="flex flex-col items-center gap-1.5 text-slate-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <span class="text-sm">{{ __('travel.g_click_drag') }}</span>
        </div>
        <div x-show="fileError" x-cloak class="flex items-center gap-2 text-red-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium" x-text="fileError"></span>
        </div>
        <div x-show="fileName" x-cloak class="flex items-center gap-3 text-emerald-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <div>
                <span class="text-sm font-medium" x-text="fileName"></span>
                <p class="text-xs text-emerald-600">{{ __('travel.g_file_selected') }}</p>
            </div>
        </div>

        <input type="file" name="c_invitation_document" accept=".pdf,.jpg,.jpeg,.png" class="hidden"
            x-ref="invitationInput"
            @change="
                const f = $event.target.files[0];
                if (f && !accept.includes(f.type)) {
                    fileError = @js(__('travel.c_invitation_bad_type')); fileName = ''; $event.target.value = '';
                } else {
                    fileError = ''; fileName = f?.name ?? '';
                }
            ">
    </label>

    @if ($existing)
        <p class="mt-1.5 text-xs text-slate-500">
            <a href="{{ route('travel-requests.invitation.download', $travelRequest) }}"
               class="font-medium text-indigo-600 hover:underline">
                {{ $travelRequest->c_invitation_original_name ?: __('travel.c_invitation_download') }}
            </a>
        </p>
    @endif

    @error('c_invitation_document')
        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
