<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جامعة الأفضل الدولية - {{ $title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background:#f3f4f6; color:#0f172a; margin:0;
               min-height:100vh; display:flex; align-items:center; justify-content:center; padding:16px; }
        .card { background:#fff; border-radius:16px; padding:28px; max-width:420px; width:100%; text-align:center;
                box-shadow:0 4px 16px rgba(0,0,0,0.08); }
        h1 { font-size:1.25rem; color:#0a2540; margin:10px 0; }
        p { color:#475569; line-height:1.8; margin:0 0 16px; }
        a { display:inline-block; background:#0a2540; color:#fff; text-decoration:none; padding:10px 22px;
            border-radius:8px; font-weight:700; }
    </style>
</head>
<body>
    <div class="card">
        <div style="font-size:2.6rem;">📄</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <a href="{{ $back }}" target="_top">العودة</a>
    </div>
</body>
</html>
