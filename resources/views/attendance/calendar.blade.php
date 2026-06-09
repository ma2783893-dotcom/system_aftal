@extends('layouts.app')
@section('title', __('Attendance Calendar'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📅 {{ __('Attendance Calendar') }}</h1>
        <a href="/" style="background:#0a2540; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem;">
            ← {{ __('Back to Dashboard') }}
        </a>
    </div>

    {{-- Employee Selector --}}
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" action="/attendance/calendar" class="flex gap-4 items-end flex-wrap">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ __('Select Employee') }}</label>
                <select name="employee_id" onchange="this.form.submit()"
                    style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-family:'Tajawal',sans-serif; min-width:200px;">
                    <option value="">{{ __('All Employees') }}</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">{{ __('Month') }}</label>
                <input type="month" name="month" value="{{ $selectedMonth }}" onchange="this.form.submit()"
                    style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-family:'Tajawal',sans-serif;">
            </div>
        </form>
    </div>

    {{-- Calendar Grid --}}
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-lg font-bold text-gray-700 mb-4">
            {{ \Carbon\Carbon::parse($selectedMonth)->translatedFormat('F Y') }}
        </h2>

        {{-- Day Headers --}}
        <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:4px; margin-bottom:4px;">
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                <div style="text-align:center; font-weight:bold; font-size:0.75rem; color:#64748b; padding:6px;">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        {{-- Day Cells --}}
        <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:4px;">
            {{-- Empty cells for first week offset --}}
            @for($i = 0; $i < $startOffset; $i++)
                <div style="padding:8px;"></div>
            @endfor

            @foreach($days as $day)
            <div style="padding:8px; text-align:center; border-radius:8px; min-height:52px;
                        background: {{ $day['present'] ? '#dcfce7' : ($day['absent'] ? '#fee2e2' : '#f8fafc') }};
                        color: {{ $day['present'] ? '#15803d' : ($day['absent'] ? '#dc2626' : '#94a3b8') }};
                        border: 1px solid {{ $day['present'] ? '#86efac' : ($day['absent'] ? '#fca5a5' : '#e2e8f0') }};">
                <div style="font-size:0.85rem; font-weight:600;">{{ $day['date'] }}</div>
                @if($day['present'])
                    <div style="font-size:0.7rem; margin-top:2px;">✓ {{ $day['hours'] }}h</div>
                @elseif($day['absent'])
                    <div style="font-size:0.7rem; margin-top:2px;">✗</div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Legend --}}
        <div style="display:flex; gap:1.5rem; margin-top:1.5rem; font-size:0.85rem;">
            <span style="display:flex; align-items:center; gap:6px;">
                <span style="width:14px; height:14px; background:#dcfce7; border-radius:3px; display:inline-block;"></span>
                {{ __('Present') }}
            </span>
            <span style="display:flex; align-items:center; gap:6px;">
                <span style="width:14px; height:14px; background:#fee2e2; border-radius:3px; display:inline-block;"></span>
                {{ __('Absent') }}
            </span>
            <span style="display:flex; align-items:center; gap:6px;">
                <span style="width:14px; height:14px; background:#f8fafc; border-radius:3px; display:inline-block;"></span>
                {{ __('No Data') }}
            </span>
        </div>
    </div>
</div>
@endsection
