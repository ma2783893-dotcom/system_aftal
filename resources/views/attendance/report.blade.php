@extends('layouts.app')
@section('title', 'تقرير الحضور')

@php use App\Support\AttendanceReport as R; @endphp

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" dir="rtl">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
        <h1 style="font-size:1.6rem; font-weight:800; color:#0a2540; margin:0;">📊 تقرير الحضور</h1>
        <a href="/" style="background:#0a2540; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem;">← العودة للوحة التحكم</a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="/attendance/report" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div class="sm:col-span-2">
                <label class="block text-sm font-bold text-gray-700 mb-1">الموظف</label>
                <select name="employee_id" class="w-full border border-gray-300 rounded-lg px-3 py-2" style="font-family:inherit;">
                    <option value="">جميع الموظفين</option>
                    @foreach($employees as $e)
                        <option value="{{ $e->id }}" @selected($report->employee && $report->employee->id === $e->id)>{{ $e->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">من تاريخ</label>
                <input type="date" name="from" value="{{ $report->from->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">إلى تاريخ</label>
                <input type="date" name="to" value="{{ $report->to->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
            <button type="submit" style="background:#2563eb; color:#fff; border:none; border-radius:8px; padding:9px 18px; font-weight:700; font-family:inherit; cursor:pointer;">🔍 عرض التقرير</button>
            <button type="submit" name="format" value="xlsx" style="background:#15803d; color:#fff; border:none; border-radius:8px; padding:9px 18px; font-weight:700; font-family:inherit; cursor:pointer;">📥 تصدير Excel</button>
            <button type="submit" name="format" value="print" formtarget="_blank" style="background:#7c3aed; color:#fff; border:none; border-radius:8px; padding:9px 18px; font-weight:700; font-family:inherit; cursor:pointer;">🖨️ طباعة / PDF</button>
            @php
                $m0 = now()->startOfMonth(); $p0 = now()->subMonthNoOverflow()->startOfMonth();
                $q  = fn($f, $t) => '/attendance/report?' . http_build_query(['employee_id' => optional($report->employee)->id, 'from' => $f->format('Y-m-d'), 'to' => $t->format('Y-m-d')]);
            @endphp
            <a href="{{ $q($m0, now()) }}" style="background:#e0e7ff; color:#3730a3; border-radius:8px; padding:9px 14px; font-weight:700; text-decoration:none;">هذا الشهر</a>
            <a href="{{ $q($p0, $p0->copy()->endOfMonth()) }}" style="background:#e0e7ff; color:#3730a3; border-radius:8px; padding:9px 14px; font-weight:700; text-decoration:none;">الشهر الماضي</a>
        </div>
    </form>

    {{-- Totals --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div style="font-size:1.6rem; font-weight:800; color:#0a2540;">{{ $report->rows->count() }}</div>
            <div style="color:#64748b; font-size:0.85rem;">يوم حضور مسجّل</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div style="font-size:1.6rem; font-weight:800; color:#1d4ed8;">{{ R::hm($report->totalHours) }}</div>
            <div style="color:#64748b; font-size:0.85rem;">إجمالي الساعات ({{ number_format($report->totalHours, 2) }})</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div style="font-size:1.6rem; font-weight:800; color:#b45309;">{{ $report->summary->sum('no_out') }}</div>
            <div style="color:#64748b; font-size:0.85rem;">يوم بدون انصراف</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
            <div style="font-size:1.6rem; font-weight:800; color:#b91c1c;">{{ $report->summary->sum('out_of_range') }}</div>
            <div style="color:#64748b; font-size:0.85rem;">تسجيل خارج النطاق</div>
        </div>
    </div>
    <p style="color:#64748b; font-size:0.85rem; margin:-4px 0 12px;">الفترة: {{ $report->period() }} — الساعات تُحسب من وقت الحضور إلى وقت الانصراف. من نسي الانصراف يُسجَّل له انصراف تلقائي الساعة {{ \App\Support\AttendanceGuard::workdayEnd() }} (من اليوم التالي).</p>

    {{-- Summary per employee --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <h2 style="font-size:1.1rem; font-weight:800; color:#0a2540; margin-bottom:10px;">الملخص لكل موظف</h2>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.9rem; min-width:560px;">
                <thead><tr style="background:#f1f5f9; text-align:right;">
                    <th style="padding:9px;">الاسم</th><th style="padding:9px;">الصفة</th><th style="padding:9px;">أيام الحضور</th>
                    <th style="padding:9px;">إجمالي الساعات</th><th style="padding:9px;">بدون انصراف</th><th style="padding:9px;">خارج النطاق</th>
                </tr></thead>
                <tbody>
                @foreach($report->summary as $s)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:8px 9px; font-weight:600;">{{ $s['name'] }}</td>
                        <td style="padding:8px 9px; color:#475569;">{{ $s['specialization'] }}</td>
                        <td style="padding:8px 9px;">{{ $s['days'] }}</td>
                        <td style="padding:8px 9px; font-weight:700; color:#1d4ed8;">{{ R::hm($s['hours']) }} <span style="color:#94a3b8; font-weight:400;">({{ number_format($s['hours'], 2) }})</span></td>
                        <td style="padding:8px 9px; color:{{ $s['no_out'] ? '#b45309' : '#94a3b8' }};">{{ $s['no_out'] }}</td>
                        <td style="padding:8px 9px; color:{{ $s['out_of_range'] ? '#b91c1c' : '#94a3b8' }};">{{ $s['out_of_range'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <h2 style="font-size:1.1rem; font-weight:800; color:#0a2540; margin-bottom:10px;">التفاصيل اليومية</h2>
        @if($report->rows->isEmpty())
            <p style="color:#94a3b8; text-align:center; padding:1.5rem 0;">لا توجد تسجيلات حضور في هذه الفترة</p>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.88rem; min-width:640px;">
                <thead><tr style="background:#f1f5f9; text-align:right;">
                    <th style="padding:9px;">الاسم</th><th style="padding:9px;">التاريخ</th><th style="padding:9px;">الحضور</th>
                    <th style="padding:9px;">الانصراف</th><th style="padding:9px;">الساعات</th><th style="padding:9px;">الموقع</th>
                </tr></thead>
                <tbody>
                @foreach($report->rows as $r)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:7px 9px;">{{ $r['name'] }}</td>
                        <td style="padding:7px 9px; white-space:nowrap;">{{ $r['date']->format('d/m/Y') }}</td>
                        <td style="padding:7px 9px;">{{ $r['in'] ?? '—' }}</td>
                        <td style="padding:7px 9px; color:{{ $r['out'] ? 'inherit' : '#b45309' }};">{{ $r['out'] ?? 'لم يسجّل' }}@if($r['auto']) <span style="font-size:0.75em; color:#7c3aed;">(تلقائي)</span>@endif</td>
                        <td style="padding:7px 9px; font-weight:700;">{{ R::hm($r['hours']) }}</td>
                        <td style="padding:7px 9px; font-size:0.8rem;">
                            {{ $r['in_ok'] ? '✅' : '⚠️' }} حضور
                            @if($r['out_ok'] !== null) · {{ $r['out_ok'] ? '✅' : '⚠️' }} انصراف @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
