@extends('layouts.app')
@section('title', 'تغيير كلمة المرور')

@section('content')
<div class="max-w-md mx-auto px-4">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 style="font-size:1.3rem; font-weight:800; color:#0a2540; margin-bottom:6px;">🔑 تغيير كلمة المرور</h2>

        @if(auth()->user()->must_change_password)
            <div style="background:#fff7ed; border:1px solid #fdba74; color:#9a3412; padding:10px 12px; border-radius:8px; font-size:0.875rem; margin-bottom:16px;">
                هذه كلمة مرور مؤقتة من الإدارة. يجب تغييرها قبل استخدام المنظومة.
            </div>
        @endif

        @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #86efac; color:#15803d; padding:10px 12px; border-radius:8px; margin-bottom:16px;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; padding:10px 12px; border-radius:8px; margin-bottom:16px; font-size:0.875rem;">
                @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="/change-password" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">كلمة المرور الحالية</label>
                <input type="password" name="current_password" required autocomplete="current-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">كلمة المرور الجديدة</label>
                <input type="password" name="password" required minlength="6" autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">6 أحرف أو أرقام على الأقل.</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">تأكيد كلمة المرور الجديدة</label>
                <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <button type="submit"
                    style="width:100%; background:#2563eb; color:#fff; font-weight:700; padding:12px; border:none; border-radius:10px; cursor:pointer; font-family:inherit;">
                حفظ كلمة المرور الجديدة
            </button>
        </form>
    </div>
</div>
@endsection
