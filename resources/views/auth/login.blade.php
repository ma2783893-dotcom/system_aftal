@extends('layouts.app')
@section('title', 'تسجيل الدخول')

@section('content')
<div style="min-height:80vh; display:flex; align-items:center; justify-content:center; position:relative;">

    <img src="/assets/logo.png" style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); width:600px; opacity:0.25; z-index:0; pointer-events:none;">

    <div class="login-card" style="position:relative; z-index:2; background:rgba(255,255,255,0.45); backdrop-filter:blur(1px); border-radius:1rem; padding:2.5rem; width:100%; max-width:28rem; box-shadow:0 10px 25px rgba(0,0,0,0.1); border:1px solid #e5e7eb;">

        <div style="text-align:center; margin-bottom:2rem;">
            <div style="text-align:center; margin-bottom:1rem;">
                <img src="/assets/logo-dark.jpg"
                     style="width:110px; height:110px; object-fit:contain;
                            border-radius:50%; margin:0 auto; display:block;">
            </div>
            <h1 style="font-size:1.6rem; font-weight:900; color:#0a2540; margin-bottom:0.5rem;">جامعة الأفضل الدولية</h1>
            <p style="font-size:0.85rem; color:#6b7280;">نظام إدارة أعضاء هيئة التدريس والموظفين</p>
        </div>

        @if($errors->any())
            <div style="background:#fef2f2; border-right:4px solid #ef4444; color:#b91c1c; padding:0.75rem 1rem; border-radius:0.5rem; margin-bottom:1.5rem;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; font-size:0.875rem; font-weight:700; color:#374151; margin-bottom:0.5rem;">البريد الإلكتروني</label>
                <input type="email" name="email" required autocomplete="username" inputmode="email" autocapitalize="none" placeholder="البريد الإلكتروني" value="{{ old('email') }}"
                    style="width:100%; padding:0.75rem 1rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:0.75rem; outline:none; font-family:inherit; box-sizing:border-box;">
            </div>
            <div style="margin-bottom:2rem;">
                <label style="display:block; font-size:0.875rem; font-weight:700; color:#374151; margin-bottom:0.5rem;">كلمة المرور</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="pw" required autocomplete="current-password" placeholder="••••••••"
                        style="width:100%; padding:0.75rem 3rem 0.75rem 1rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:0.75rem; outline:none; font-family:inherit; box-sizing:border-box;">
                    <button type="button" aria-label="إظهار كلمة المرور"
                        onclick="var p=document.getElementById('pw'); p.type = p.type === 'password' ? 'text' : 'password'; this.textContent = p.type === 'password' ? '👁️' : '🙈';"
                        style="position:absolute; top:50%; right:0.6rem; transform:translateY(-50%); background:none; border:none; font-size:1.15rem; cursor:pointer; padding:4px;">👁️</button>
                </div>
                <p style="font-size:0.75rem; color:#94a3b8; margin:6px 0 0;">اضغط 👁️ لترى ما تكتبه وتتأكد منه</p>
            </div>
            <button type="submit"
                style="width:100%; background:#0a2540; color:white; font-weight:700; padding:0.875rem; border-radius:0.75rem; border:none; cursor:pointer; font-family:inherit; font-size:1rem; transition: background 0.3s ease;">
                تسجيل الدخول ❯
            </button>
        </form>
    </div>
</div>

<style>
.login-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.login-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15) !important;
}
</style>

<script>
    // إذا بقيت صفحة الدخول مفتوحة طويلاً، أعد تحميلها قبل الإرسال حتى لا يظهر خطأ 419
    (function () {
        var openedAt = Date.now();
        var MAX_AGE = 60 * 60 * 1000; // ساعة
        function stale() { return Date.now() - openedAt > MAX_AGE; }
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && stale()) location.reload();
        });
        window.addEventListener('pageshow', function (e) { if (e.persisted) location.reload(); });
        var form = document.querySelector('form[action$="/login"]');
        if (form) form.addEventListener('submit', function (e) {
            if (stale()) { e.preventDefault(); location.reload(); }
        });
    })();

    // Force light mode on login page — does NOT modify localStorage
    setTimeout(function() {
        document.body.classList.remove('dark-mode');
        document.documentElement.classList.remove('dark-mode');
        var cb = document.getElementById('darkModeCheckbox');
        if (cb) cb.checked = false;
    }, 0);
</script>
@endsection