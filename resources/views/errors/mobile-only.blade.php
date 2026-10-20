<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جامعة الأفضل الدولية - متاح من الهاتف فقط</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background:#0a2540; color:#fff; margin:0;
               min-height:100vh; display:flex; align-items:center; justify-content:center; padding:16px; }
        .card { background:#fff; color:#0a2540; border-radius:16px; padding:32px; max-width:420px; text-align:center; }
        h1 { font-size:1.4rem; margin:12px 0; }
        p { color:#475569; line-height:1.7; }
        button { background:#ef4444; color:#fff; border:none; padding:10px 24px; border-radius:8px;
                 font-family:inherit; font-weight:700; cursor:pointer; margin-top:12px; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:3rem;">📱</div>
        <h1>المنظومة متاحة من الهاتف المحمول فقط</h1>
        <p>حسابات الموظفين لا تعمل من أجهزة الكمبيوتر.<br>يرجى فتح الرابط من هاتفك المسجّل.</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">تسجيل الخروج</button>
        </form>
    </div>
</body>
</html>
