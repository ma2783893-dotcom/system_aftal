@extends('layouts.app')
@section('title', 'النسخة الاحتياطية')

@section('content')
<div class="max-w-3xl mx-auto px-4">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
        <h1 style="font-size:1.5rem; font-weight:800; color:#0a2540; margin:0;">💾 النسخة الاحتياطية</h1>
        <a href="/" style="background:#0a2540; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem;">← العودة للوحة التحكم</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5">
        <p style="line-height:1.9; color:#334155; margin:0 0 12px;">
            حمّل نسخة من كل بيانات المنظومة واحفظها في جهازك أو في Google Drive. لو صار أي خطأ أو انمسحت بيانات،
            هذا الملف يرجّع كل شي كما كان. <b>ننصحك بالتحميل مرة كل أسبوع.</b>
        </p>
        <div style="background:{{ $last ? '#f0fdf4' : '#fef2f2' }}; color:{{ $last ? '#15803d' : '#b91c1c' }}; padding:10px 12px; border-radius:8px; margin-bottom:16px; font-weight:700;">
            {{ $last ? 'آخر نسخة حُمّلت: ' . $last : 'لم تُحمَّل أي نسخة بعد' }}
        </div>

        <form method="POST" action="/admin/backup" style="display:flex; flex-direction:column; gap:10px;">
            @csrf
            <button name="files" value="0" style="background:#0f766e; color:#fff; border:none; padding:14px; border-radius:10px; font-weight:800; font-size:1rem; cursor:pointer; font-family:inherit;">
                ⬇️ تحميل البيانات (سريع وصغير)
            </button>
            <button name="files" value="1" style="background:#fff; color:#0f766e; border:2px solid #0f766e; padding:12px; border-radius:10px; font-weight:800; cursor:pointer; font-family:inherit;">
                ⬇️ تحميل كامل مع السير الذاتية والصور (ملف كبير)
            </button>
        </form>
        <p style="font-size:0.8rem; color:#94a3b8; margin:10px 0 0;">الملف بصيغة ‎.sql‎ — لا تفتحه ولا تعدّله، فقط احفظه. الاسترجاع يتم من قاعدة البيانات في Railway.</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h2 style="font-size:1.05rem; font-weight:800; color:#0a2540; margin:0 0 10px;">ما يحتويه الملف</h2>
        <table style="width:100%; border-collapse:collapse; font-size:0.88rem;">
            @foreach($stats as $table => $count)
                <tr style="border-top:1px solid #f1f5f9;">
                    <td style="padding:6px 4px; direction:ltr; text-align:right; color:#475569;">{{ $table }}</td>
                    <td style="padding:6px 4px; font-weight:700; width:90px;">{{ $count ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
