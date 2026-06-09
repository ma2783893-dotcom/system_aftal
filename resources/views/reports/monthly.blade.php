<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; direction: rtl; }
    .header { text-align: center; border-bottom: 2px solid #0a2540; padding-bottom: 12px; margin-bottom: 20px; }
    .header h1 { color: #0a2540; font-size: 18px; margin: 0 0 4px; }
    .header p  { color: #64748b; font-size: 11px; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #0a2540; color: white; padding: 8px; text-align: right; font-size: 11px; }
    td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .total-row td { background: #e0f2fe; font-weight: bold; }
    .footer { margin-top: 30px; font-size: 10px; color: #94a3b8; text-align: center; }
    .sigs { display: flex; justify-content: space-between; margin-top: 50px; }
    .sig { text-align: center; width: 200px; }
    .sig-line { border-top: 1px solid #333; margin-top: 40px; }
</style>
</head>
<body>
<div class="header">
    <h1>جامعة الأفضل الدولية — Al Afdal International University</h1>
    <p>التقرير المالي الشهري — {{ $month }}</p>
    <p>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>اسم الموظف</th>
            <th>التخصص</th>
            <th>الراتب الأساسي</th>
            <th>العلاوات</th>
            <th>الخصومات</th>
            <th>الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        @php $grandTotal = 0; @endphp
        @foreach($employees as $i => $emp)
        @php
            $salary     = $emp->finance?->salary ?? 0;
            $bonus      = $emp->finance?->bonus ?? 0;
            $deductions = $emp->finance?->deductions ?? 0;
            $net        = $salary + $bonus - $deductions;
            $grandTotal += $net;
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $emp->name }}</td>
            <td>{{ $emp->specialization }}</td>
            <td>{{ number_format($salary, 2) }}</td>
            <td>{{ number_format($bonus, 2) }}</td>
            <td>{{ number_format($deductions, 2) }}</td>
            <td><strong>{{ number_format($net, 2) }}</strong></td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="6" style="text-align:right;">الإجمالي الكلي</td>
            <td>{{ number_format($grandTotal, 2) }}</td>
        </tr>
    </tbody>
</table>

<div class="sigs">
    <div class="sig">
        <p>رئيس الجامعة</p>
        <div class="sig-line"></div>
        <p style="font-size:10px; color:#94a3b8;">التوقيع</p>
    </div>
    <div class="sig">
        <p>محاسب الجامعة</p>
        <div class="sig-line"></div>
        <p style="font-size:10px; color:#94a3b8;">التوقيع</p>
    </div>
</div>

<div class="footer">© 2026 جامعة الأفضل الدولية — جميع الحقوق محفوظة</div>
</body>
</html>
