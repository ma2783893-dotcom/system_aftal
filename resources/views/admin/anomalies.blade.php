@extends('layouts.app')
@section('title', 'كاشف التلاعب')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px;">
        <h1 style="font-size:1.5rem; font-weight:800; color:#0a2540; margin:0;">🕵️ كاشف التلاعب في الحضور</h1>
        <a href="/" style="background:#0a2540; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem;">← العودة للوحة التحكم</a>
    </div>

    <div style="background:#eff6ff; border:1px solid #bfdbfe; color:#1e3a8a; padding:12px 14px; border-radius:10px; font-size:0.88rem; line-height:1.9; margin-bottom:14px;">
        هذه الصفحة <b>لا تعاقب أحداً</b>، تعرض فقط تسجيلات تحتاج مراجعة. راجع كل حالة واسأل الموظف قبل أي قرار.
    </div>

    <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center; margin-bottom:14px;">
        <span style="font-size:0.85rem; color:#475569;">الفترة:</span>
        @foreach([7 => 'أسبوع', 30 => 'شهر', 90 => '3 أشهر'] as $k => $label)
            <a href="?days={{ $k }}" style="padding:6px 14px; border-radius:999px; text-decoration:none; font-size:0.85rem; font-weight:700;
               {{ $days === $k ? 'background:#0a2540; color:#fff;' : 'background:#f1f5f9; color:#334155;' }}">{{ $label }}</a>
        @endforeach
        <span style="font-size:0.8rem; color:#94a3b8; margin-right:auto;">فُحص {{ $checked }} تسجيل</span>
    </div>

    @forelse($findings as $f)
        @php $c = ['high' => ['#dc2626', 'خطورة عالية'], 'medium' => ['#d97706', 'متوسطة'], 'low' => ['#64748b', 'منخفضة']][$f['level']]; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-100" style="padding:14px 16px; margin-bottom:10px; border-right:5px solid {{ $c[0] }};">
            <div style="display:flex; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                <b style="color:#0f172a;">{{ $f['title'] }}</b>
                <span style="background:{{ $c[0] }}; color:#fff; font-size:0.72rem; padding:2px 10px; border-radius:999px;">{{ $c[1] }}</span>
            </div>
            <div style="margin-top:6px; font-weight:700; color:#0a2540;">{{ implode('، ', $f['people']) }}</div>
            <div style="margin-top:4px; font-size:0.88rem; color:#334155; line-height:1.8;">{{ $f['detail'] }}</div>
            <div style="margin-top:4px; font-size:0.8rem; color:#64748b;">💡 {{ $f['why'] }}</div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-gray-100" style="padding:2rem; text-align:center; color:#15803d; font-weight:700;">
            ✅ لا توجد حالات مشبوهة في هذه الفترة
        </div>
    @endforelse

    <p style="font-size:0.78rem; color:#94a3b8; margin-top:12px; line-height:1.8;">
        فحص "دقة GPS الثابتة" يبدأ يشتغل من اليوم، لأن المنظومة ما كانتش تحفظ دقة الموقع قبل هذا التحديث.
    </p>
</div>
@endsection
