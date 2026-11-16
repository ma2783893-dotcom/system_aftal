@extends('layouts.app')
@section('title', 'نسيت كلمة المرور')

@section('content')
<div style="min-height:70vh; display:flex; align-items:center; justify-content:center; padding:0 12px;">
    <div style="background:#fff; border-radius:1rem; padding:2rem; width:100%; max-width:26rem; box-shadow:0 10px 25px rgba(0,0,0,0.08); border:1px solid #e5e7eb;">
        <h1 style="font-size:1.35rem; font-weight:900; color:#0a2540; margin:0 0 6px;">نسيت كلمة المرور؟</h1>
        <p style="font-size:0.88rem; color:#6b7280; margin:0 0 18px; line-height:1.8;">
            اكتب بريدك الإلكتروني ويصل طلبك للإدارة. بعد الموافقة تعطيك الإدارة كلمة مرور جديدة.
        </p>

        @if($errors->any())
            <div style="background:#fef2f2; border-right:4px solid #ef4444; color:#b91c1c; padding:0.6rem 0.9rem; border-radius:0.5rem; margin-bottom:1rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/forgot-password">
            @csrf
            <label style="display:block; font-size:0.875rem; font-weight:700; color:#374151; margin-bottom:0.4rem;">البريد الإلكتروني</label>
            <input type="email" name="email" required inputmode="email" autocapitalize="none" value="{{ old('email') }}"
                   style="width:100%; padding:0.75rem 1rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:0.75rem; box-sizing:border-box; font-family:inherit; margin-bottom:1rem;">

            <label style="display:block; font-size:0.875rem; font-weight:700; color:#374151; margin-bottom:0.4rem;">ملاحظة للإدارة (اختياري)</label>
            <input type="text" name="reason" maxlength="300" value="{{ old('reason') }}" placeholder="مثلاً: رقم هاتفي 09xxxxxxxx"
                   style="width:100%; padding:0.75rem 1rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:0.75rem; box-sizing:border-box; font-family:inherit; margin-bottom:1.25rem;">

            <button type="submit" style="width:100%; background:#0a2540; color:#fff; font-weight:700; padding:0.85rem; border:none; border-radius:0.75rem; cursor:pointer; font-family:inherit; font-size:1rem;">
                إرسال الطلب للإدارة
            </button>
        </form>
        <div style="text-align:center; margin-top:14px;">
            <a href="/login" style="color:#2563eb; font-size:0.9rem;">← رجوع لتسجيل الدخول</a>
        </div>
    </div>
</div>
@endsection
