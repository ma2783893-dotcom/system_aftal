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
            @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d)
                <div style="text-align:center; font-weight:bold; font-size:0.75rem; color:#64748b; padding:6px;">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Day Cells --}}
        <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:4px;">
            @for($i = 0; $i < $startOffset; $i++)
                <div style="padding:8px;"></div>
            @endfor

            @foreach($days as $day)
            @php
                $dateAttendances = $attendancesByDate[$day['dateStr']] ?? collect();
                $hasGps = $dateAttendances->whereNotNull('latitude')->count() > 0;
            @endphp
            <div style="padding:6px; border-radius:8px; min-height:64px;
                        background: {{ $day['present'] ? '#dcfce7' : '#f8fafc' }};
                        border: 1px solid {{ $day['present'] ? '#86efac' : '#e2e8f0' }};">
                <div style="font-size:0.85rem; font-weight:600; color:{{ $day['present'] ? '#15803d' : '#94a3b8' }}; text-align:center;">
                    {{ $day['date'] }}
                </div>
                @foreach($dateAttendances as $att)
                <div onclick="showAttendanceDetail({{ $att->id }})"
                     style="cursor:pointer; font-size:0.68rem; margin-top:3px; padding:2px 4px; border-radius:4px;
                            background:{{ $att->location_verified ? '#bbf7d0' : '#fed7aa' }};
                            color:{{ $att->location_verified ? '#15803d' : '#c2410c' }};"
                     title="{{ $att->employee->name ?? '' }} — {{ $att->check_in_time }}">
                    {{ $att->location_verified ? '📍' : '⚠️' }}
                    {{ Str::limit($att->employee->name ?? '—', 10) }}
                    @if($att->check_in_time)
                        <span style="color:#6b7280;">({{ substr($att->check_in_time,0,5) }})</span>
                    @endif
                </div>
                @endforeach
                @if($day['present'] && $dateAttendances->isEmpty())
                    <div style="font-size:0.7rem; text-align:center; color:#15803d; margin-top:2px;">✓ {{ $day['hours'] }}h</div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Legend --}}
        <div style="display:flex; gap:1.5rem; margin-top:1.5rem; font-size:0.8rem; flex-wrap:wrap;">
            <span style="display:flex; align-items:center; gap:6px;">
                <span style="width:14px; height:14px; background:#bbf7d0; border-radius:3px; display:inline-block;"></span>
                📍 {{ __('Present') }} (موقع موثق)
            </span>
            <span style="display:flex; align-items:center; gap:6px;">
                <span style="width:14px; height:14px; background:#fed7aa; border-radius:3px; display:inline-block;"></span>
                ⚠️ خارج النطاق
            </span>
            <span style="display:flex; align-items:center; gap:6px;">
                <span style="width:14px; height:14px; background:#f8fafc; border-radius:3px; display:inline-block;"></span>
                {{ __('No Data') }}
            </span>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div id="detailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4 shadow-2xl">
        <div id="detailContent" class="text-center text-gray-500">⏳ جاري التحميل...</div>
        <button onclick="document.getElementById('detailModal').classList.add('hidden')"
                class="mt-5 w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 rounded-xl">
            إغلاق
        </button>
    </div>
</div>

<script>
function showAttendanceDetail(id) {
    document.getElementById('detailModal').classList.remove('hidden');
    document.getElementById('detailContent').innerHTML = '⏳ جاري التحميل...';

    fetch('/attendance/detail/' + id)
        .then(r => r.json())
        .then(data => {
            const mapLink = data.lat && data.lng
                ? `<a href="https://maps.google.com/?q=${data.lat},${data.lng}" target="_blank"
                      style="display:block; text-align:center; background:#3b82f6; color:white;
                             padding:8px; border-radius:10px; text-decoration:none; margin-top:12px; font-weight:600;">
                      📍 عرض الموقع على خرائط Google
                   </a>` : '';

            document.getElementById('detailContent').innerHTML = `
                <h3 style="font-weight:700; font-size:1.1rem; margin-bottom:12px;">${data.name}</h3>
                <p style="color:#6b7280; margin-bottom:6px;">⏰ وقت الحضور: <strong>${data.time || '—'}</strong></p>
                <p style="color:#6b7280; margin-bottom:6px;">📏 المسافة من الجامعة: <strong>${data.distance} متر</strong></p>
                <div style="margin:10px 0;">
                    ${data.verified
                        ? '<span style="background:#dcfce7; color:#15803d; padding:4px 14px; border-radius:20px; font-weight:700;">✅ موقع موثق داخل النطاق</span>'
                        : '<span style="background:#fee2e2; color:#dc2626; padding:4px 14px; border-radius:20px; font-weight:700;">⚠️ خارج نطاق الجامعة</span>'}
                </div>
                ${mapLink}`;
        })
        .catch(() => {
            document.getElementById('detailContent').innerHTML = '<p style="color:#dc2626;">حدث خطأ في التحميل</p>';
        });
}
</script>
@endsection
