@extends('layouts.app')
@section('title', __('Profile') . ' - ' . $emp->name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="/" class="text-blue-500 hover:text-blue-700 underline font-medium">&larr; {{ __('Back to Dashboard') }}</a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">{{ $emp->name }}</h1>
            <span class="bg-indigo-100 text-indigo-800 text-sm font-semibold px-3 py-1 rounded-full">{{ $emp->specialization ?? __('Not Available') }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <p class="text-sm text-gray-500 mb-1">{{ __('Email') }}</p>
                <p class="font-bold text-gray-800">{{ $emp->email }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 mb-1">{{ __('Role') }}</p>
                <p class="font-bold capitalize text-gray-800">{{ $emp->role }}</p>
            </div>
        </div>

        {{-- Financial Overview — ADMIN ONLY --}}
        @if(auth()->user()->role === 'admin')
        <div class="mb-8 border-t border-gray-100 pt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('Financial Overview') }}</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">{{ __('Total Hours') }}</p>
                    <p class="font-bold text-lg text-blue-600">{{ $emp->attendances->sum('hours_worked') }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">{{ __('Salary') }}</p>
                    <p class="font-bold text-lg text-green-600">{{ number_format($emp->finance->salary ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">{{ __('Bonus') }}</p>
                    <p class="font-bold text-lg text-green-500">+{{ number_format($emp->finance->bonus ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">{{ __('Deductions') }}</p>
                    <p class="font-bold text-lg text-red-500">-{{ number_format($emp->finance->deductions ?? 0, 2) }}</p>
                </div>
            </div>

            @livewire('employee-finance-calculator', ['employeeId' => $emp->id])
        </div>
        @endif

        {{-- ============================================================
             SUBJECTS & SALARY SECTION — ADMIN ONLY
        ============================================================ --}}
        @if(auth()->user()->role === 'admin')
        <div class="mb-8 border-t border-gray-100 pt-6">

            {{-- Success / Error Flash --}}
            @if(session('subject_success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-5 py-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('subject_success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 rounded-lg px-5 py-3">
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Section Header --}}
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    {{ __('Subjects & Salary Management') }}
                </h2>
                <span class="bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full">{{ __('Admin Only') }}</span>
            </div>

            {{-- Add Subject Form --}}
            <div class="bg-gradient-to-br from-slate-50 to-indigo-50 border border-indigo-100 rounded-xl p-6 mb-6">
                <h3 class="text-base font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add New Subject') }}
                </h3>
                <form action="/admin/employees/{{ $emp->id }}/subjects" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">{{ __('Subject Name') }}</label>
                            <input type="text"
                                   name="subject_name"
                                   id="subject_name_input"
                                   value="{{ old('subject_name') }}"
                                   placeholder="{{ __('e.g. Database, AI, Math') }}"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white outline-none transition"
                                   required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">{{ __('Hourly Rate') }} ({{ __('LYD') }})</label>
                            <input type="number"
                                   name="hourly_rate"
                                   id="hourly_rate_input"
                                   value="{{ old('hourly_rate') }}"
                                   placeholder="0.00"
                                   step="0.01"
                                   min="0"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white outline-none transition"
                                   required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">{{ __('Worked Hours') }}</label>
                            <input type="number"
                                   name="worked_hours"
                                   id="worked_hours_input"
                                   value="{{ old('worked_hours') }}"
                                   placeholder="0"
                                   step="0.5"
                                   min="0"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white outline-none transition"
                                   required>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                                id="add_subject_btn"
                                class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 transition-all text-white font-semibold px-6 py-2.5 rounded-lg text-sm shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add Subject') }}
                        </button>
                    </div>
                </form>
            </div>

            {{-- Subjects Table --}}
            @if($emp->subjects->count() > 0)
            <div class="overflow-x-auto rounded-xl border border-gray-200 mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-indigo-700 text-white">
                        <tr>
                            <th class="px-5 py-3 text-start font-semibold">#</th>
                            <th class="px-5 py-3 text-start font-semibold">{{ __('Subject Name') }}</th>
                            <th class="px-5 py-3 text-start font-semibold">{{ __('Hourly Rate') }}</th>
                            <th class="px-5 py-3 text-start font-semibold">{{ __('Worked Hours') }}</th>
                            <th class="px-5 py-3 text-start font-semibold">{{ __('Subject Total') }}</th>
                            <th class="px-5 py-3 text-center font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($emp->subjects as $index => $subject)
                        <tr class="hover:bg-indigo-50 transition-colors">
                            <td class="px-5 py-3 text-gray-400 font-mono">{{ $index + 1 }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-800">{{ $subject->subject_name }}</td>
                            <td class="px-5 py-3 text-blue-700 font-medium">{{ number_format($subject->hourly_rate, 2) }} {{ __('LYD') }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ number_format($subject->worked_hours, 1) }} {{ __('hr') }}</td>
                            <td class="px-5 py-3 text-green-700 font-bold">{{ number_format($subject->total_amount, 2) }} {{ __('LYD') }}</td>
                            <td class="px-5 py-3 text-center">
                                <form action="/admin/subjects/{{ $subject->id }}" method="POST" class="inline"
                                      onsubmit="return confirm('{{ __('Are you sure you want to delete this subject?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors rounded-md px-3 py-1.5 text-xs font-semibold border border-red-200 flex items-center gap-1 mx-auto">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        {{ __('Delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Grand Total Card --}}
            <div class="bg-gradient-to-r from-indigo-700 to-indigo-900 rounded-xl p-6 flex items-center justify-between shadow-lg">
                <div>
                    <p class="text-indigo-200 text-sm font-medium mb-1">{{ __('Grand Total Salary') }}</p>
                    <p class="text-white text-3xl font-extrabold tracking-tight">
                        {{ number_format($emp->grandTotalSalary(), 2) }}
                        <span class="text-indigo-300 text-lg font-semibold ms-1">{{ __('LYD') }}</span>
                    </p>
                    <p class="text-indigo-300 text-xs mt-1">{{ __('Sum of all subject totals (Hourly Rate × Worked Hours)') }}</p>
                </div>
                <div class="bg-white/10 rounded-full p-4">
                    <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>

            @else
            {{-- Empty State --}}
            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-xl py-10 text-center text-gray-400">
                <svg class="mx-auto w-10 h-10 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <p class="text-sm font-medium">{{ __('No subjects added yet.') }}</p>
                <p class="text-xs mt-1">{{ __('Use the form above to add subjects and calculate salary.') }}</p>
            </div>
            @endif

        </div>
        @endif

        <div>
            <h2 class="text-xl font-bold text-gray-800 mb-4">{{ __('Resume') }}</h2>
            @if($emp->cv)
                <div class="border rounded-lg overflow-hidden h-screen max-h-[800px]">
                    <iframe src="{{ route('resume.view', $emp->id) }}" class="w-full h-full" frameborder="0"></iframe>
                </div>
            @else
                <div class="bg-gray-50 p-6 rounded-lg text-center text-gray-500 border border-gray-200">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('No resume uploaded.') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
