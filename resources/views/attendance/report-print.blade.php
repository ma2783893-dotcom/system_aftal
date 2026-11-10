<!DOCTYPE html>
<html translate="no" lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="google" content="notranslate">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->title() }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    @php use App\Support\AttendanceReport as R; @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tajawal', 'DejaVu Sans', sans-serif; color:#0f172a; margin:0; background:#e5e7eb; }
        .bar { position:sticky; top:0; background:#0a2540; padding:10px 14px; display:flex; gap:8px; flex-wrap:wrap; }
        .bar button, .bar a { border:none; border-radius:8px; padding:8px 16px; font-weight:700; font-family:inherit; cursor:pointer; text-decoration:none; font-size:0.9rem; }
        .page { background:#fff; max-width:297mm; margin:14px auto; padding:14mm 12mm; }
        .head { display:flex; align-items:center; gap:14px; border-bottom:3px solid #0a2540; padding-bottom:10px; margin-bottom:12px; }
        .head img { height:64px; }
        h1 { margin:0; font-size:20px; color:#0a2540; }
        .sub { color:#475569; font-size:13px; margin-top:4px; }
        h2 { font-size:15px; color:#0a2540; margin:16px 0 6px; }
        table { width:100%; border-collapse:collapse; font-size:12px; }
        th { background:#0a2540; color:#fff; padding:6px; text-align:right; }
        td { padding:5px 6px; border-bottom:1px solid #e2e8f0; }
        tr:nth-child(even) td { background:#f8fafc; }
        tfoot td { font-weight:800; background:#e0e7ff !important; }
        .warn { color:#b45309; }
        .foot { margin-top:14px; font-size:11px; color:#94a3b8; display:flex; justify-content:space-between; }
        @page { size: A4 landscape; margin: 10mm; }
        @media print {
            body { background:#fff; }
            .bar { display:none; }
            .page { margin:0; padding:0; max-width:none; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="bar">
        <button onclick="window.print()" style="background:#16a34a; color:#fff;">🖨️ طباعة / حفظ PDF</button>
        <a href="javascript:history.back()" style="background:#e5e7eb; color:#111827;">رجوع</a>
    </div>

    <div class="page">
        <div class="head">
            <img src="{{ asset('assets/logo-print.png') }}" alt="" onerror="this.style.display='none'">
            <div>
                <h1>جامعة الأفضل الدولية — {{ $report->title() }}</h1>
                <div class="sub">الفترة: {{ $report->period() }} · عدد أيام الحضور: {{ $report->rows->count() }} · إجمالي الساعات: {{ R::hm($report->totalHours) }} ({{ number_format($report->totalHours, 2) }})</div>
            </div>
        </div>

        <h2>الملخص لكل موظف</h2>
        <table>
            <thead><tr><th>#</th><th>الاسم</th><th>الصفة</th><th>أيام الحضور</th><th>إجمالي الساعات</th><th>بدون انصراف</th><th>خارج النطاق</th><th>التوقيع</th></tr></thead>
            <tbody>
            @foreach($report->summary as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td><td>{{ $s['name'] }}</td><td>{{ $s['specialization'] }}</td><td>{{ $s['days'] }}</td>
                    <td><b>{{ R::hm($s['hours']) }}</b> ({{ number_format($s['hours'], 2) }})</td>
                    <td class="{{ $s['no_out'] ? 'warn' : '' }}">{{ $s['no_out'] }}</td>
                    <td class="{{ $s['out_of_range'] ? 'warn' : '' }}">{{ $s['out_of_range'] }}</td>
                    <td style="width:14%;"></td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr>
                <td colspan="3">الإجمالي</td><td>{{ $report->summary->sum('days') }}</td>
                <td>{{ R::hm($report->totalHours) }} ({{ number_format($report->totalHours, 2) }})</td>
                <td>{{ $report->summary->sum('no_out') }}</td><td>{{ $report->summary->sum('out_of_range') }}</td><td></td>
            </tr></tfoot>
        </table>

        @if($report->rows->isNotEmpty())
        <h2>التفاصيل اليومية</h2>
        <table>
            <thead><tr><th>الاسم</th><th>التاريخ</th><th>الحضور</th><th>الانصراف</th><th>الساعات</th><th>موقع الحضور</th><th>موقع الانصراف</th></tr></thead>
            <tbody>
            @foreach($report->rows as $r)
                <tr>
                    <td>{{ $r['name'] }}</td><td>{{ $r['date']->format('d/m/Y') }}</td><td>{{ $r['in'] ?? '—' }}</td>
                    <td class="{{ $r['out'] ? '' : 'warn' }}">{{ $r['out'] ?? 'لم يسجّل' }}@if($r['auto']) <span style="font-size:0.75em; color:#7c3aed;">(تلقائي)</span>@endif</td>
                    <td>{{ R::hm($r['hours']) }}</td>
                    <td>{{ $r['in_ok'] ? 'داخل النطاق' : 'خارج النطاق' }}</td>
                    <td>{{ $r['out_ok'] === null ? '—' : ($r['out_ok'] ? 'داخل النطاق' : 'خارج النطاق') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        <div class="foot"><span>جامعة الأفضل الدولية</span><span>تاريخ الطباعة: {{ now()->format('d/m/Y H:i') }}</span></div>
    </div>
</body>
</html>
