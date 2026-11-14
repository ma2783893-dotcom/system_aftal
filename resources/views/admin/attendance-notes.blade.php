@extends('layouts.app')
@section('title', 'ملاحظات واستثناءات الحضور')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
        <h1 style="font-size:1.5rem; font-weight:800; color:#0a2540; margin:0;">📝 ملاحظات واستثناءات الحضور</h1>
        <a href="/" style="background:#0a2540; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem;">← العودة للوحة التحكم</a>
    </div>

    @if(session('success'))
        <div style="background:#f0fdf4; border:1px solid #86efac; color:#15803d; padding:10px 12px; border-radius:8px; margin-bottom:14px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; padding:10px 12px; border-radius:8px; margin-bottom:14px;">
            @foreach($errors->all() as $e)<div>⚠️ {{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="/admin/attendance-notes" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        @csrf
        <p style="color:#475569; font-size:0.9rem; line-height:1.8; margin:0 0 14px;">
            استخدمها لما موظف يطلع بإذن أو في مهمة، أو نسي يسجّل، أو عنده إجازة:
            اختر الموظف (أو أكثر من واحد)، والتاريخ، ثم اكتب <b>وقت الخروج</b> و/أو <b>وقت الحضور</b> و/أو <b>ملاحظة</b>.
            الساعات تُحسب تلقائياً.
        </p>

        <label class="block text-sm font-bold text-gray-700 mb-1">الموظفين</label>
        <div style="border:1px solid #e5e7eb; border-radius:10px; padding:8px 10px; max-height:220px; overflow-y:auto; margin-bottom:6px;">
            <label style="display:flex; gap:8px; align-items:center; padding:4px 0; font-weight:700; border-bottom:1px solid #f1f5f9; cursor:pointer;">
                <input type="checkbox" onclick="document.querySelectorAll('.emp-cb').forEach(c => c.checked = this.checked)"> تحديد الكل
            </label>
            @foreach($employees as $e)
                <label style="display:flex; gap:8px; align-items:center; padding:4px 0; cursor:pointer;">
                    <input type="checkbox" class="emp-cb" name="employee_ids[]" value="{{ $e->id }}"
                           @checked(in_array($e->id, old('employee_ids', $preselect)))>
                    <span>{{ $e->name }}</span>
                    <span style="color:#94a3b8; font-size:0.78rem;">{{ $e->specialization }}</span>
                </label>
            @endforeach
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">📅 التاريخ</label>
                <input type="date" name="date" required value="{{ old('date', now()->toDateString()) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">🟢 وقت الحضور (اختياري)</label>
                <input type="time" name="check_in" value="{{ old('check_in') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">🔴 وقت الخروج (اختياري)</label>
                <input type="time" name="check_out" value="{{ old('check_out') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </div>

        <div class="mt-3">
            <label class="block text-sm font-bold text-gray-700 mb-1">🗒️ الملاحظة (اختياري)</label>
            <input type="text" name="note" maxlength="500" value="{{ old('note') }}" list="noteIdeas"
                   placeholder="مثال: خروج بإذن، مهمة رسمية، إجازة مرضية..." class="w-full border border-gray-300 rounded-lg px-3 py-2">
            <datalist id="noteIdeas">
                <option value="خروج بإذن"><option value="مهمة رسمية خارج الجامعة"><option value="إجازة"><option value="إجازة مرضية">
                <option value="نسي تسجيل الخروج"><option value="نسي تسجيل الحضور"><option value="دورة تدريبية">
            </datalist>
        </div>

        <button type="submit" style="width:100%; margin-top:16px; background:#2563eb; color:#fff; font-weight:800; padding:13px; border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-size:1rem;">
            💾 حفظ
        </button>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 style="font-size:1.1rem; font-weight:800; color:#0a2540; margin:0 0 10px;">آخر الملاحظات والتعديلات (90 يوم)</h2>
        @if($entries->isEmpty())
            <p style="color:#94a3b8; text-align:center; padding:1.5rem 0;">لا توجد ملاحظات أو تعديلات بعد</p>
        @else
            <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.88rem; min-width:560px;">
                <thead><tr style="background:#f1f5f9; text-align:right;">
                    <th style="padding:8px;">التاريخ</th><th style="padding:8px;">الموظف</th><th style="padding:8px;">الحضور</th>
                    <th style="padding:8px;">الخروج</th><th style="padding:8px;">الملاحظة</th><th style="padding:8px;"></th>
                </tr></thead>
                <tbody>
                @foreach($entries as $a)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:7px 8px; white-space:nowrap;">{{ \Carbon\Carbon::parse($a->date)->format('d/m/Y') }}</td>
                        <td style="padding:7px 8px;">{{ $a->employee->name }}</td>
                        <td style="padding:7px 8px;">{{ $a->check_in_time ? substr($a->check_in_time, 0, 5) : '—' }}</td>
                        <td style="padding:7px 8px;">{{ $a->check_out_time ? substr($a->check_out_time, 0, 5) : '—' }}</td>
                        <td style="padding:7px 8px;">{{ $a->note }} @if($a->manual)<span style="font-size:0.72rem; color:#7c3aed;">(بواسطة الإدارة)</span>@endif</td>
                        <td style="padding:7px 8px;">
                            @if($a->note || ($a->status === 'absent' && !$a->check_in_time))
                            <form method="POST" action="/admin/attendance-notes/{{ $a->id }}/delete" style="margin:0;" onsubmit="return confirm('حذف هذه الملاحظة؟')">
                                @csrf
                                <button style="background:#fee2e2; color:#b91c1c; border:none; border-radius:6px; padding:4px 10px; cursor:pointer; font-family:inherit; font-size:0.8rem;">حذف</button>
                            </form>
                            @endif
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
