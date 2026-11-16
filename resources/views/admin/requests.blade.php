@extends('layouts.app')
@section('title', 'طلبات الموظفين')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
        <h1 style="font-size:1.5rem; font-weight:800; color:#0a2540; margin:0;">📥 طلبات الموظفين</h1>
        <a href="/" style="background:#0a2540; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem;">← العودة للوحة التحكم</a>
    </div>

    @if(session('success'))
        <div style="background:#f0fdf4; border:1px solid #86efac; color:#15803d; padding:10px 12px; border-radius:8px; margin-bottom:14px;">{{ session('success') }}</div>
    @endif

    @if($np = session('new_password'))
        <div style="background:#fffbeb; border:2px solid #f59e0b; padding:16px; border-radius:12px; margin-bottom:16px;">
            <div style="font-weight:800; color:#92400e; margin-bottom:8px;">🔑 كلمة المرور الجديدة — تظهر مرة واحدة فقط، أرسلها للموظف الآن</div>
            <div id="npText" style="background:#fff; border-radius:8px; padding:12px; line-height:2; font-size:0.95rem;">
                {{ $np['name'] }}<br>
                <span dir="ltr">البريد: {{ $np['email'] }}</span><br>
                <span dir="ltr">كلمة المرور: <b style="font-size:1.3rem; letter-spacing:3px; color:#b45309;">{{ $np['password'] }}</b></span><br>
                <span dir="ltr">{{ url('/login') }}</span>
            </div>
            <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap;">
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('npText').innerText).then(()=>{this.innerText='✅ تم النسخ'})"
                        style="background:#0a2540; color:#fff; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-family:inherit;">📋 نسخ</button>
                <a href="https://wa.me/?text={{ rawurlencode($np['name'] . "\nالبريد: " . $np['email'] . "\nكلمة المرور: " . $np['password'] . "\n" . url('/login')) }}" target="_blank" rel="noopener"
                   style="background:#16a34a; color:#fff; padding:8px 16px; border-radius:8px; text-decoration:none;">إرسال بالواتساب</a>
            </div>
        </div>
    @endif

    @php $tabs = ['pending' => 'بانتظار الرد', 'approved' => 'الموافَق عليها', 'rejected' => 'المرفوضة', 'all' => 'الكل']; @endphp
    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:14px;">
        @foreach($tabs as $k => $label)
            <a href="?status={{ $k }}" style="padding:7px 14px; border-radius:999px; text-decoration:none; font-size:0.85rem; font-weight:700;
               {{ $status === $k ? 'background:#0a2540; color:#fff;' : 'background:#f1f5f9; color:#334155;' }}">
                {{ $label }}@if($k !== 'all') ({{ $counts[$k] ?? 0 }})@endif
            </a>
        @endforeach
    </div>

    @forelse($requests as $r)
        @php
            $color = ['leave' => '#2563eb', 'late' => '#d97706', 'early' => '#9333ea', 'password' => '#dc2626'][$r->type] ?? '#334155';
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100" style="padding:14px 16px; margin-bottom:10px; border-right:5px solid {{ $color }};">
            <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                <div>
                    <span style="background:{{ $color }}; color:#fff; font-size:0.75rem; padding:2px 10px; border-radius:999px; font-weight:700;">{{ $r->typeLabel() }}</span>
                    <b style="margin-right:6px;">{{ $r->user->name ?? $r->email }}</b>
                </div>
                <span style="font-size:0.78rem; color:#94a3b8;">{{ $r->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div style="margin-top:8px; font-size:0.92rem; color:#0f172a;">{{ $r->summary() }}</div>
            @if($r->reason)
                <div style="margin-top:4px; font-size:0.88rem; color:#475569;">السبب: {{ $r->reason }}</div>
            @endif

            @if($r->status === 'pending')
                <form method="POST" action="/admin/requests/{{ $r->id }}" style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; align-items:center;">
                    @csrf
                    <input type="text" name="admin_note" maxlength="500" placeholder="ملاحظة (اختياري)"
                           style="flex:1; min-width:160px; padding:8px 10px; border:1px solid #e5e7eb; border-radius:8px; font-family:inherit;">
                    <button name="decision" value="approve" style="background:#16a34a; color:#fff; border:none; padding:8px 18px; border-radius:8px; font-weight:700; cursor:pointer; font-family:inherit;"
                            @if($r->type === 'password') onclick="return confirm('سيتم إنشاء كلمة مرور جديدة لهذا الموظف فقط. متابعة؟')" @endif>✔ موافقة</button>
                    <button name="decision" value="reject" style="background:#e5e7eb; color:#111827; border:none; padding:8px 18px; border-radius:8px; font-weight:700; cursor:pointer; font-family:inherit;">✖ رفض</button>
                </form>
            @else
                <div style="margin-top:8px; font-size:0.82rem; color:{{ $r->status === 'approved' ? '#15803d' : '#b91c1c' }};">
                    {{ $r->statusLabel() }} — {{ optional($r->decided_at)->format('d/m/Y H:i') }}
                    @if($r->admin_note) · {{ $r->admin_note }} @endif
                </div>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-xl border border-gray-100" style="padding:2rem; text-align:center; color:#94a3b8;">لا توجد طلبات هنا.</div>
    @endforelse

    <p style="font-size:0.8rem; color:#94a3b8; margin-top:14px; line-height:1.8;">
        عند الموافقة على إجازة أو إذن تُكتب ملاحظة تلقائياً في أيام الطلب، وتظهر في تقرير الحضور وصفحة الملاحظات.
    </p>
</div>
@endsection
