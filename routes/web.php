<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| 1. روابط الخدمات (اللغة والسيرة الذاتية)
|--------------------------------------------------------------------------
*/
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');
Route::get('/resume/view/{id}', [ResumeController::class, 'view'])->name('resume.view');
Route::get('/resume/download/{id}', [ResumeController::class, 'download'])->name('resume.download');

/*
|--------------------------------------------------------------------------
| 2. روابط الدخول والخروج
|--------------------------------------------------------------------------
*/

// عرض صفحة الدخول
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// عملية الدخول الفعلية
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    return back()->withErrors([
        'email' => 'البيانات المدخلة غير صحيحة.',
    ]);
});

// تسجيل الخروج
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

/*
|--------------------------------------------------------------------------
| 3. لوحة التحكم وإدارة الموظفين
|--------------------------------------------------------------------------
*/

// الصفحة الرئيسية - عرض الموظفين
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::user()->role === 'employee') {
        return redirect('/profile/' . \Illuminate\Support\Facades\Auth::id());
    }
    
    $employees = User::where('role', 'employee')->get();
    return view('admin.dashboard', compact('employees'));
})->middleware('auth');

// ملف الموظف الشخصي
Route::get('/profile/{id}', function ($id) {
    if (\Illuminate\Support\Facades\Auth::user()->role === 'employee' && \Illuminate\Support\Facades\Auth::id() != $id) {
        abort(403, 'Unauthorized. You can only view your own profile.');
    }

    $emp = User::with(['finance', 'attendances', 'subjects'])->findOrFail($id);
    return view('admin.profile', compact('emp'));
})->middleware('auth');

// إضافة مادة لموظف (Admin فقط)
Route::post('/admin/employees/{id}/subjects', [SubjectController::class, 'store'])->middleware('auth');

// حذف مادة (Admin فقط)
Route::delete('/admin/subjects/{id}', [SubjectController::class, 'destroy'])->middleware('auth');

// تحديث مالية الموظف (المسؤول فقط)
Route::post('/profile/{id}/finance', function (Request $request, $id) {
    if (\Illuminate\Support\Facades\Auth::user()->role !== 'admin') {
        abort(403, 'Unauthorized.');
    }
    $finance = \App\Models\Finance::updateOrCreate(
        ['employee_id' => $id],
        ['salary' => $request->salary ?? 0, 'bonus' => $request->bonus ?? 0, 'deductions' => $request->deductions ?? 0]
    );
    return back()->with('success', 'تم التحديث المالي بنجاح');
})->middleware('auth');

// صفحة إضافة موظف جديد
Route::get('/create-employee', function () {
    if (Illuminate\Support\Facades\Auth::user()->role !== 'admin') abort(403);
    return view('admin.create_employee');
})->middleware('auth');

// معالجة إضافة الموظف (مع التشفير ورفع الملف)
Route::post('/add-employee', function (Request $request) {
    if (Illuminate\Support\Facades\Auth::user()->role !== 'admin') abort(403);
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'specialization' => 'required|string|max:255',
        'password' => 'required|string|min:6',
        'cv' => 'nullable|mimes:pdf|max:2048'
    ]);

    $cvFileName = null;
    if ($request->hasFile('cv')) {
        $cvFileName = time() . '_' . $request->cv->getClientOriginalName();
        $request->cv->move(public_path('uploads/cv'), $cvFileName);
    }

    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'specialization' => $request->specialization,
        'role' => 'employee',
        'cv' => $cvFileName,
        'password' => Hash::make($request->password) // تشفير كلمة السر
    ]);

    return redirect('/')->with('success', 'تم إضافة الموظف بنجاح');
})->middleware('auth');

// حذف الموظف مع ملفه من الجهاز
Route::get('/delete-employee/{id}', function ($id) {
    if (Illuminate\Support\Facades\Auth::user()->role !== 'admin') abort(403);
    $emp = User::findOrFail($id);

    if ($emp->cv && file_exists(public_path('uploads/cv/' . $emp->cv))) {
        unlink(public_path('uploads/cv/' . $emp->cv));
    }

    $emp->delete();
    return redirect('/')->with('success', 'تم حذف الموظف');
})->middleware('auth');

// تعديل الموظف (عرض الصفحة - تم إكماله هنا بنجاح)
Route::get('/edit-employee/{id}', function ($id) {
    if (Illuminate\Support\Facades\Auth::user()->role !== 'admin') abort(403);
    $emp = User::findOrFail($id);
    return view('admin.edit', compact('emp'));
})->middleware('auth');

// تحديث بيانات الموظف (تم إكماله هنا بنجاح)
Route::post('/update-employee/{id}', function (Request $request, $id) {
    if (Illuminate\Support\Facades\Auth::user()->role !== 'admin') abort(403);
    $emp = User::findOrFail($id);

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $id,
        'specialization' => 'required|string|max:255',
        'cv' => 'nullable|mimes:pdf|max:2048'
    ]);

    if ($request->hasFile('cv')) {
        // حذف الملف القديم أولاً إن وجد
        if ($emp->cv && file_exists(public_path('uploads/cv/' . $emp->cv))) {
            unlink(public_path('uploads/cv/' . $emp->cv));
        }
        $cvFileName = time() . '_' . $request->cv->getClientOriginalName();
        $request->cv->move(public_path('uploads/cv'), $cvFileName);
        $emp->cv = $cvFileName;
    }

    $emp->name = $request->name;
    $emp->email = $request->email;
    $emp->specialization = $request->specialization;
    $emp->save();

    return redirect('/')->with('success', 'تم تحديث البيانات بنجاح');
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| 4. مفتاح تشغيل الـ Migrations وإنشاء حساب الآدمن الافتراضي تلقائياً
|--------------------------------------------------------------------------
*/
Route::get('/run-migrate', function () {
    try {
        // 1. تشغيل التهجير وبناء الجداول في ريلواي
        \Artisan::call('migrate', ['--force' => true]);
        
        // 2. إنشاء حساب الآدمن الخاص بالجامعة تلقائياً إذا كان الجدول فارغاً
        $adminEmail = 'admin@aftal.edu.ly';
        $adminExists = \App\Models\User::where('email', $adminEmail)->exists();
        
        if (!$adminExists) {
            \App\Models\User::create([
                'name' => 'إدارة جامعة الأفضل الدولية',
                'email' => $adminEmail,
                'specialization' => 'System Admin',
                'role' => 'admin',
                'password' => \Illuminate\Support\Facades\Hash::make('Aftal@2026') // كلمة المرور الافتراضية
            ]);
            $adminMessage = '<div style="background:#e8f8f5; border:1px solid #a3e4d7; padding:15px; border-radius:5px; margin-top:15px;">
                                <h3 style="color:#117a65; margin-top:0;">🔐 تم إنشاء حساب الآدمن الأساسي بنجاح!</h3>
                                <p style="margin:5px 0;"><b>البريد الإلكتروني:</b> <code>' . $adminEmail . '</code></p>
                                <p style="margin:5px 0;"><b>كلمة المرور المؤقتة:</b> <code>Aftal@2026</code></p>
                             </div>';
        } else {
            $adminMessage = '<p style="color:#2980b9;">ℹ️ حساب الآدمن موجود مسبقاً في قاعدة البيانات وجاهز للاستخدام.</p>';
        }

        return '<div style="text-align:center; margin-top:50px; font-family:sans-serif; direction:rtl; max-width:500px; margin-left:auto; margin-right:auto; padding:20px; border:1px solid #d6dbdf; border-radius:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    <h1 style="color:#2ecc71;">منظومة الأفضل جاهزة للعمل! 🎉</h1>
                    <p style="color:#7f8c8d;">تمت مزامنة قاعدة البيانات بنجاح تام.</p>
                    ' . $adminMessage . '
                    <br>
                    <a href="' . url('/login') . '" style="display:inline-block; padding:12px 25px; background:#3498db; color:#fff; text-decoration:none; border-radius:5px; font-weight:bold; margin-top:10px;">الانتقال إلى واجهة الدخول الرسمية 🚀</a>
                </div>';

    } catch (\Exception $e) {
        return '<div style="padding:20px; font-family:monospace; background:#fadbd8; color:#78281f; border:1px solid #f5b7b1; direction:ltr; text-align:left;">
                    <h3>حدث خطأ أثناء الـ Migrate أو إنشاء الآدمن:</h3>
                    <p>' . nl2br(e($e->getMessage())) . '</p>
                </div>';
    }
});