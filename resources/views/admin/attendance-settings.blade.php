@extends('layouts.app')
@section('title', 'إعدادات الحضور')

@section('content')
<div class="max-w-3xl mx-auto px-4">
    <div class="mb-4">
        <a href="/" class="text-blue-500 hover:text-blue-700 underline font-medium">&larr; العودة للوحة التحكم</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 sm:p-6">
        <h2 style="font-size:1.35rem; font-weight:800; color:#0a2540; margin-bottom:6px;">📍 نطاق الجامعة على الخريطة</h2>
        <p style="color:#64748b; font-size:0.9rem; margin-bottom:16px; line-height:1.8;">
            الموظف يقدر يسجّل حضور أو انصراف إذا كان داخل هذه الدائرة. لو الهاتف يعطي موقعاً غير دقيق
            يُسمح بهامش إضافي حتى {{ \App\Support\AttendanceGuard::MAX_GPS_TOLERANCE }} متر.
        </p>

        @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #86efac; color:#15803d; padding:10px 12px; border-radius:8px; margin-bottom:14px;">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div style="background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; padding:10px 12px; border-radius:8px; margin-bottom:14px;">
                @foreach($errors->all() as $e)<div>⚠️ {{ $e }}</div>@endforeach
            </div>
        @endif

        <div style="position:relative; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; margin-bottom:16px; height:280px; background:#f1f5f9;">
            <iframe id="mapFrame" title="الخريطة" width="100%" height="100%" style="border:0;" loading="lazy"
                    src="https://maps.google.com/maps?q={{ $lat }},{{ $lng }}&z=17&output=embed"></iframe>
        </div>

        <form method="POST" action="/admin/attendance-settings" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">نصف قطر النطاق: أبعد مسافة مسموحة من مركز الجامعة (متر)</label>
                <input type="number" name="radius" id="radius" min="20" max="5000" step="10" required
                       value="{{ old('radius', $radius) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2" style="font-size:1.1rem; font-weight:700;">
                <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:8px;">
                    @foreach([100, 200, 300, 500, 1000] as $r)
                        <button type="button" onclick="document.getElementById('radius').value={{ $r }}"
                                style="background:#e0e7ff; color:#3730a3; border:none; border-radius:999px; padding:5px 14px; font-weight:700; cursor:pointer; font-family:inherit;">{{ $r }} م</button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">خط العرض (مركز الجامعة)</label>
                    <input type="text" inputmode="decimal" name="lat" id="lat" required value="{{ old('lat', $lat) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2" dir="ltr">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">خط الطول (مركز الجامعة)</label>
                    <input type="text" inputmode="decimal" name="lng" id="lng" required value="{{ old('lng', $lng) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2" dir="ltr">
                </div>
            </div>

            <button type="button" onclick="useMyLocation()"
                    style="width:100%; background:#0f766e; color:#fff; font-weight:700; padding:11px; border:none; border-radius:10px; cursor:pointer; font-family:inherit;">
                📡 استخدم موقعي الحالي كمركز للجامعة
            </button>
            <p id="geoMsg" style="font-size:0.82rem; color:#64748b; margin:0;">اضغط هذا الزر وأنت واقف في وسط الجامعة من هاتفك.</p>

            <div style="border-top:1px dashed #e2e8f0; padding-top:14px;">
                <h3 style="font-size:1.05rem; font-weight:800; color:#0a2540; margin:0 0 6px;">🕒 الانصراف التلقائي</h3>
                <label style="display:flex; gap:8px; align-items:center; cursor:pointer; margin-bottom:10px;">
                    <input type="checkbox" name="auto_checkout" value="1" {{ old('auto_checkout', $autoCheckout) ? 'checked' : '' }} style="width:18px; height:18px;">
                    <span>لو الموظف نسي يسجّل انصراف، يُسجَّل له انصراف تلقائي في نهاية الدوام</span>
                </label>
                <label class="block text-sm font-bold text-gray-700 mb-1">وقت نهاية الدوام</label>
                <input type="time" name="workday_end" required value="{{ old('workday_end', $workdayEnd) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2" style="max-width:200px;">
                <p style="font-size:0.8rem; color:#64748b; margin:6px 0 0; line-height:1.7;">
                    يُطبَّق على الأيام السابقة فقط (من أمس وقبل)، لأن اليوم الحالي ممكن الموظف ما زال في الدوام.
                    في التقرير يظهر هذا الانصراف بعلامة "تلقائي".
                </p>
            </div>

            <button type="submit"
                    style="width:100%; background:#2563eb; color:#fff; font-weight:800; padding:13px; border:none; border-radius:10px; cursor:pointer; font-family:inherit; font-size:1rem;">
                💾 حفظ النطاق
            </button>
        </form>
    </div>
</div>

<script>
function refreshMap() {
    const lat = parseFloat(document.getElementById('lat').value), lng = parseFloat(document.getElementById('lng').value);
    if (!isNaN(lat) && !isNaN(lng)) {
        document.getElementById('mapFrame').src = 'https://maps.google.com/maps?q=' + lat + ',' + lng + '&z=17&output=embed';
    }
}
document.getElementById('lat').addEventListener('change', refreshMap);
document.getElementById('lng').addEventListener('change', refreshMap);

function useMyLocation() {
    const msg = document.getElementById('geoMsg');
    if (!navigator.geolocation) { msg.textContent = 'المتصفح لا يدعم تحديد الموقع'; return; }
    msg.textContent = '⏳ جاري تحديد موقعك...';
    navigator.geolocation.getCurrentPosition(function (p) {
        document.getElementById('lat').value = p.coords.latitude.toFixed(7);
        document.getElementById('lng').value = p.coords.longitude.toFixed(7);
        msg.textContent = '✅ تم أخذ موقعك (دقة ±' + Math.round(p.coords.accuracy) + ' متر). راجع الخريطة ثم اضغط حفظ.';
        refreshMap();
    }, function (e) {
        msg.textContent = e.code === 1 ? '❌ الموقع مرفوض. اسمح للمتصفح بالوصول لموقعك وحاول مرة أخرى.' : '❌ تعذر تحديد الموقع، حاول مرة أخرى.';
    }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
}
</script>
@endsection
