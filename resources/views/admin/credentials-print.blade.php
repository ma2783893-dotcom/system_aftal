<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>بطاقات الدخول - جامعة الأفضل الدولية</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Tajawal', sans-serif; background: #e5e7eb; margin: 0; color: #0f172a; }
        .toolbar { position: sticky; top: 0; z-index: 10; background: #0a2540; color: #fff; padding: 12px 16px;
                   display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .toolbar .warn { font-size: 0.85rem; color: #fde68a; }
        .btn { border: none; border-radius: 8px; padding: 9px 16px; font-family: inherit; font-weight: 700;
               cursor: pointer; font-size: 0.9rem; text-decoration: none; display: inline-block; }
        .btn-green { background: #16a34a; color: #fff; }
        .btn-blue  { background: #2563eb; color: #fff; }
        .btn-gray  { background: #e5e7eb; color: #111827; }
        .pages { padding: 20px 12px; display: flex; flex-direction: column; align-items: center; gap: 20px; }

        .page { background: #fff; width: 210mm; max-width: 100%; min-height: 297mm; padding: 18mm 16mm;
                box-shadow: 0 4px 16px rgba(0,0,0,0.12); position: relative; display: flex; flex-direction: column; }
        .head { text-align: center; border-bottom: 3px solid #0a2540; padding-bottom: 14px; margin-bottom: 22px; }
        .head img { height: 80px; margin-bottom: 6px; }
        .head h1 { margin: 0; font-size: 24px; color: #0a2540; font-weight: 800; }
        .head p { margin: 4px 0 0; color: #64748b; font-size: 14px; }
        .hello { font-size: 17px; line-height: 1.9; margin-bottom: 18px; }
        .hello strong { color: #0a2540; }
        .box { border: 2px solid #0a2540; border-radius: 14px; overflow: hidden; margin-bottom: 20px; }
        .row { display: flex; border-bottom: 1px solid #e2e8f0; }
        .row:last-child { border-bottom: none; }
        .row .k { width: 34%; background: #f1f5f9; padding: 14px 16px; font-weight: 700; color: #334155; font-size: 15px; }
        .row .v { flex: 1; padding: 14px 16px; font-size: 17px; font-weight: 700; direction: ltr; text-align: left;
                  word-break: break-all; }
        .row .v.ar { direction: rtl; text-align: right; }
        .pw { font-family: 'Courier New', monospace; font-size: 24px !important; letter-spacing: 3px; color: #b45309; }
        .qr-wrap { display: flex; gap: 18px; align-items: center; margin-bottom: 20px; }
        .qr { width: 130px; height: 130px; flex-shrink: 0; }
        .qr-wrap p { margin: 0; font-size: 14px; color: #475569; line-height: 1.8; }
        .steps { background: #f8fafc; border-radius: 12px; padding: 14px 18px; font-size: 14.5px; line-height: 2; }
        .steps h3 { margin: 0 0 4px; font-size: 16px; color: #0a2540; }
        .steps ol { margin: 0; padding-right: 20px; }
        .note { margin-top: 14px; font-size: 13px; color: #b91c1c; line-height: 1.8; }
        .foot { margin-top: auto; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 12px; color: #94a3b8;
                display: flex; justify-content: space-between; }
        .card-actions { position: absolute; top: 10px; left: 10px; }

        table.list { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.list th { background: #0a2540; color: #fff; padding: 8px; text-align: right; }
        table.list td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        table.list td.ltr { direction: ltr; text-align: left; }
        table.list tr:nth-child(even) td { background: #f8fafc; }

        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff; }
            .toolbar, .card-actions, .no-print { display: none !important; }
            .pages { padding: 0; gap: 0; }
            .page { box-shadow: none; width: 210mm; min-height: 297mm; page-break-after: always; break-after: page; }
            .page:last-child { page-break-after: auto; break-after: auto; }
            body.print-one .page { display: none !important; }
            body.print-one .page.selected { display: flex !important; }
            body:not(.print-list) .page.list-page { display: none !important; }
            body.print-list .page:not(.list-page) { display: none !important; }
        }
        @media screen and (max-width: 700px) {
            .page { min-height: auto; padding: 18px 14px; }
            .row { flex-direction: column; }
            .row .k { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn btn-green" onclick="printAll()">📄 حفظ كل البطاقات PDF / طباعة</button>
            <button class="btn btn-blue" onclick="printList()">📋 القائمة المجمّعة للمدير</button>
            <a class="btn btn-gray" href="/">العودة للوحة التحكم</a>
        </div>
        <div class="warn">⚠️ تظهر كلمات المرور مرة واحدة فقط. احفظ الملفات قبل مغادرة الصفحة.</div>
    </div>

    <div class="pages">
        @foreach($cards as $i => $c)
        <section class="page" id="card-{{ $i }}" data-name="{{ $c['name'] }}">
            <div class="card-actions">
                <button class="btn btn-green" onclick="printOne({{ $i }})">📄 حفظ PDF لهذا الموظف</button>
            </div>
            <div class="head">
                <img src="{{ asset('assets/logo-print.png') }}" alt="" onerror="this.style.display='none'">
                <h1>جامعة الأفضل الدولية</h1>
                <p>منظومة إدارة أعضاء هيئة التدريس — بيانات الدخول الشخصية</p>
            </div>

            <div class="hello">
                السيد/ة <strong>{{ $c['name'] }}</strong> المحترم/ة،<br>
                نرحّب بكم في منظومة الجامعة. فيما يلي بيانات دخولكم الشخصية، يُرجى الحفاظ على سريّتها.
            </div>

            <div class="box">
                <div class="row"><div class="k">الاسم</div><div class="v ar">{{ $c['name'] }}</div></div>
                @if($c['specialization'])
                <div class="row"><div class="k">التخصص</div><div class="v ar">{{ $c['specialization'] }}</div></div>
                @endif
                <div class="row"><div class="k">رابط المنظومة</div><div class="v">{{ $loginUrl }}</div></div>
                <div class="row"><div class="k">البريد الإلكتروني</div><div class="v">{{ $c['email'] }}</div></div>
                <div class="row"><div class="k">كلمة المرور المؤقتة</div><div class="v pw">{{ $c['password'] }}</div></div>
            </div>

            <div class="qr-wrap">
                <div class="qr" data-url="{{ $loginUrl }}"></div>
                <p>امسح الرمز بكاميرا الهاتف لفتح المنظومة مباشرة.<br>
                   <strong>ملاحظة:</strong> حساب الموظف يعمل من الهاتف المحمول فقط.</p>
            </div>

            <div class="steps">
                <h3>خطوات الدخول لأول مرة</h3>
                <ol>
                    <li>افتح الرابط أعلاه من هاتفك المحمول.</li>
                    <li>أدخل البريد الإلكتروني وكلمة المرور المؤقتة كما هي (الأحرف الكبيرة والصغيرة مختلفة).</li>
                    <li>ستظهر صفحة تغيير كلمة المرور: اختر كلمة مرور جديدة خاصة بك (6 أحرف أو أرقام على الأقل).</li>
                    <li>أول هاتف تسجّل منه الحضور يُربط بحسابك، ولا يمكن استخدام هاتف آخر إلا بعد مراجعة الإدارة.</li>
                </ol>
            </div>

            <div class="note">🔒 لا تشارك كلمة المرور مع أي شخص. كلمة المرور المؤقتة تتوقف بمجرد تغييرها.</div>

            <div class="foot">
                <span>جامعة الأفضل الدولية</span>
                <span>تاريخ الإصدار: {{ now()->format('d/m/Y') }}</span>
            </div>
        </section>
        @endforeach

        <section class="page list-page" id="list-page">
            <div class="head">
                <h1>القائمة المجمّعة لبيانات الدخول</h1>
                <p>للاستخدام الإداري فقط — {{ count($cards) }} موظف — {{ now()->format('d/m/Y H:i') }}</p>
            </div>
            <p style="font-size:13px; color:#475569; margin-top:0;">رابط المنظومة: <span dir="ltr">{{ $loginUrl }}</span></p>
            <table class="list">
                <thead><tr><th>#</th><th>الاسم</th><th>البريد الإلكتروني</th><th>كلمة المرور المؤقتة</th><th>التوقيع بالاستلام</th></tr></thead>
                <tbody>
                    @foreach($cards as $i => $c)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $c['name'] }}</td>
                        <td class="ltr">{{ $c['email'] }}</td>
                        <td class="ltr" style="font-family:'Courier New',monospace; font-weight:700;">{{ $c['password'] }}</td>
                        <td style="width:22%;"></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>

    <script>
        document.querySelectorAll('.qr').forEach(function (el) {
            try { new QRCode(el, { text: el.dataset.url, width: 130, height: 130, correctLevel: QRCode.CorrectLevel.M }); }
            catch (e) { el.style.display = 'none'; }
        });

        var baseTitle = document.title;
        function resetPrint() {
            document.body.classList.remove('print-one', 'print-list');
            document.querySelectorAll('.page.selected').forEach(function (p) { p.classList.remove('selected'); });
            document.title = baseTitle;
        }
        window.addEventListener('afterprint', resetPrint);

        function printOne(i) {
            resetPrint();
            var page = document.getElementById('card-' + i);
            page.classList.add('selected');
            document.body.classList.add('print-one');
            document.title = 'بيانات الدخول - ' + page.dataset.name; // اسم ملف PDF
            window.print();
        }
        function printAll() {
            resetPrint();
            document.title = 'بطاقات الدخول - جميع الموظفين';
            window.print();
        }
        function printList() {
            resetPrint();
            document.body.classList.add('print-list');
            document.title = 'القائمة المجمعة لبيانات الدخول';
            window.print();
        }

        window.addEventListener('beforeunload', function (e) { e.preventDefault(); e.returnValue = ''; });
    </script>
</body>
</html>
