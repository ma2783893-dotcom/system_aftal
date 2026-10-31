@extends('layouts.app')
@section('title', 'بطاقات الدخول')

@section('content')
<div class="max-w-3xl mx-auto px-4">
    <div class="mb-4">
        <a href="/" class="text-blue-500 hover:text-blue-700 underline font-medium">&larr; العودة للوحة التحكم</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 style="font-size:1.4rem; font-weight:800; color:#0a2540; margin-bottom:8px;">🔐 بطاقات الدخول للموظفين</h2>

        <div style="background:#fff7ed; border:1px solid #fdba74; color:#9a3412; padding:12px 14px; border-radius:10px; font-size:0.9rem; line-height:1.8; margin-bottom:16px;">
            <strong>تنبيه قبل الضغط:</strong>
            كلمات المرور الحالية محفوظة مشفّرة ولا يمكن عرضها. هذه الصفحة تُنشئ <strong>كلمة مرور مؤقتة جديدة</strong>
            لكل موظف تختاره، وكلمته القديمة <strong>تتوقف فوراً</strong>. بعد الإنشاء تظهر البطاقات
            <strong>مرة واحدة فقط</strong>، فاحفظها PDF أو اطبعها مباشرة. كل موظف سيُطلب منه تغيير كلمة المرور عند أول دخول.
        </div>

        @if($errors->any())
            <div style="background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; padding:10px 12px; border-radius:8px; margin-bottom:12px;">
                @foreach($errors->all() as $e)<div>❌ {{ $e }}</div>@endforeach
            </div>
        @endif

        @if($employees->isEmpty())
            <p style="color:#6b7280; text-align:center; padding:2rem 0;">لا يوجد موظفون.</p>
        @else
        <form method="POST" action="/admin/credentials"
              onsubmit="return confirm('سيتم إنشاء كلمات مرور جديدة للموظفين المحددين وتتوقف كلماتهم القديمة. متابعة؟')">
            @csrf
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
                <label style="font-weight:700; cursor:pointer;">
                    <input type="checkbox" id="checkAll" checked onclick="document.querySelectorAll('.emp-cb').forEach(c => c.checked = this.checked)">
                    تحديد الكل ({{ $employees->count() }})
                </label>
            </div>

            <div style="border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; margin-bottom:16px;">
                <table style="width:100%; border-collapse:collapse; font-size:0.9rem;">
                    <thead>
                        <tr style="background:#f1f5f9; text-align:right;">
                            <th style="padding:10px; width:40px;"></th>
                            <th style="padding:10px;">#</th>
                            <th style="padding:10px;">الاسم</th>
                            <th style="padding:10px;">البريد الإلكتروني</th>
                            <th style="padding:10px;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $i => $emp)
                        <tr style="border-top:1px solid #f1f5f9;">
                            <td style="padding:8px 10px;"><input type="checkbox" class="emp-cb" name="employee_ids[]" value="{{ $emp->id }}" checked></td>
                            <td style="padding:8px 10px; color:#94a3b8;">{{ $i + 1 }}</td>
                            <td style="padding:8px 10px; font-weight:600;">{{ $emp->name }}</td>
                            <td style="padding:8px 10px; direction:ltr; text-align:right;">{{ $emp->email }}</td>
                            <td style="padding:8px 10px; font-size:0.78rem;">
                                @if($emp->must_change_password)
                                    <span style="color:#b45309;">بانتظار أول دخول</span>
                                @else
                                    <span style="color:#15803d;">نشط</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <label style="display:flex; gap:8px; align-items:flex-start; margin-bottom:16px; cursor:pointer; font-size:0.9rem;">
                <input type="checkbox" name="confirm" value="1" required style="margin-top:4px;">
                <span>أفهم أن كلمات المرور القديمة للموظفين المحددين ستتوقف، وأن البطاقات تظهر مرة واحدة فقط.</span>
            </label>

            <button type="submit"
                    style="width:100%; background:#b45309; color:#fff; font-weight:800; padding:14px; border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-size:1rem;">
                🔐 إنشاء البطاقات للموظفين المحددين
            </button>
        </form>
        @endif
    </div>
</div>
@endsection
