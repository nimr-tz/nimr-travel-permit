<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('users.title') }}</h1>
                <p class="page-sub">{{ __('users.subtitle') }}</p>
            </div>
            <a href="{{ route('users.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                {{ __('users.new_user') }}
            </a>
        </div>
    </x-slot>

    <div class="p-6 max-w-7xl mx-auto space-y-5">

        @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
            class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="flex-1">{{ session('status') }}</span>
            <button @click="show = false" class="text-emerald-400 hover:text-emerald-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        @endif

        @php
            $roleColors = [
                'staff'            => 'bg-slate-100 text-slate-600',
                'head'             => 'bg-blue-100 text-blue-700',
                'manager'          => 'bg-violet-100 text-violet-700',
                'director'         => 'bg-indigo-100 text-indigo-700',
                'centre_manager'   => 'bg-cyan-100 text-cyan-700',
                'director_general' => 'bg-amber-100 text-amber-700',
                'hr'               => 'bg-emerald-100 text-emerald-700',
            ];
        @endphp

        <div class="card overflow-hidden">
            <table class="w-full">
                <thead class="table-head">
                    <tr>
                        <th class="table-th">{{ __('users.col_user') }}</th>
                        <th class="table-th hidden md:table-cell">{{ __('users.col_email') }}</th>
                        <th class="table-th hidden lg:table-cell">{{ __('users.col_unit') }}</th>
                        <th class="table-th">{{ __('users.col_role') }}</th>
                        <th class="table-th hidden sm:table-cell">{{ __('users.col_status') }}</th>
                        <th class="table-th w-16 text-right">{{ __('users.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                    <tr class="table-row group">
                        <td class="table-td">
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700 shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 text-sm">{{ $user->name }}</div>
                                    @if ($user->staff_number)
                                    <div class="text-xs text-slate-400 font-mono">{{ $user->staff_number }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="table-td hidden md:table-cell text-slate-500 text-sm">{{ $user->email }}</td>
                        <td class="table-td hidden lg:table-cell">
                            <div class="text-sm text-slate-700">{{ $user->unit?->name ?? '—' }}</div>
                            @if ($user->unit)
                            <div class="text-xs text-slate-400 mt-0.5">{{ __('common.unit_' . $user->unit->type) }}</div>
                            @endif
                        </td>
                        <td class="table-td">
                            <span class="badge {{ $roleColors[$user->role] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ __('common.role_' . $user->role) }}
                            </span>
                        </td>
                        <td class="table-td hidden sm:table-cell">
                            @if ($user->is_active)
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('users.active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>{{ __('users.inactive') }}
                                </span>
                            @endif
                        </td>
                        <td class="table-td text-right">
                            <a href="{{ route('users.edit', $user) }}"
                                class="btn-ghost btn-sm text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50">
                                {{ __('users.edit_btn') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <p class="text-sm text-slate-400">{{ __('users.no_users') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
        <div class="flex justify-center">
            {{ $users->links() }}
        </div>
        @endif

    </div>
</x-app-layout>
