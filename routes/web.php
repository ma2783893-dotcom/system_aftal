<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Finance;
use App\Models\Attendance;
use App\Models\AppNotification;
use App\Models\ActivityLog;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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
    if (Auth::user()->role === 'employee') {
        return redirect('/profile/' . Auth::id());
    }

    $employees       = User::where('role', 'employee')->get();
    $totalEmployees  = $employees->count();
    $totalHours      = Attendance::sum('hours_worked') ?? 0;
    $totalSalaries   = Finance::sum('salary') ?? 0;
    $totalDepartments = User::where('role', 'employee')->distinct('specialization')->count('specialization');
    $specializations  = User::where('role', 'employee')->whereNotNull('specialization')->distinct()->pluck('specialization');

    return view('admin.dashboard', compact(
        'employees', 'totalEmployees', 'totalHours',
        'totalSalaries', 'totalDepartments', 'specializations'
    ));
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
    if (Auth::user()->role !== 'admin') abort(403);
    $request->validate([
        'name'          => 'required|string|max:255',
        'email'         => 'required|email|unique:users,email',
        'specialization'=> 'required|string|max:255',
        'password'      => 'required|string|min:6',
        'cv'            => 'nullable|mimes:pdf|max:2048',
        'profile_photo' => 'nullable|image|max:2048',
    ]);

    $cvFileName    = null;
    $photoFileName = null;

    if ($request->hasFile('cv')) {
        $cvFileName = time() . '_' . $request->cv->getClientOriginalName();
        $request->cv->move(public_path('uploads/cv'), $cvFileName);
    }

    if ($request->hasFile('profile_photo')) {
        $photoFileName = time() . '_' . $request->profile_photo->getClientOriginalName();
        $request->profile_photo->move(public_path('uploads/photos'), $photoFileName);
    }

    try {
        $employee = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'specialization'=> $request->specialization,
            'role'          => 'employee',
            'cv'            => $cvFileName,
            'profile_photo' => $photoFileName,
            'password'      => Hash::make($request->password),
        ]);
    } catch (\Exception $e) {
        \Log::error('Employee create failed: ' . $e->getMessage());
        return back()->withErrors(['error' => 'فشل إضافة الموظف: ' . $e->getMessage()]);
    }

    \Log::info('Employee created: ID=' . $employee->id . ' Name=' . $employee->name);
    $dbCheck = \DB::table('users')->where('id', $employee->id)->first();
    return response()->json([
        'status'           => 'created',
        'model_data'       => $employee->toArray(),
        'db_direct'        => $dbCheck,
        'total_employees'  => \DB::table('users')->where('role', 'employee')->count(),
    ]);

    // Notification
    try {
        AppNotification::create([
            'title'   => 'موظف جديد',
            'message' => 'تم إضافة موظف جديد: ' . $employee->name,
            'is_read' => false,
        ]);
    } catch (\Exception $e) {}

    // Activity Log
    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'added_employee',
            'target'     => $employee->name,
            'details'    => 'Added new employee with email: ' . $employee->email,
            'ip_address' => $request->ip(),
        ]);
    } catch (\Exception $e) {}

    return redirect('/')->with('success', 'تم إضافة الموظف بنجاح — ID: ' . $employee->id);
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

// تحديث بيانات الموظف
Route::post('/update-employee/{id}', function (Request $request, $id) {
    if (Auth::user()->role !== 'admin') abort(403);
    $emp = User::findOrFail($id);

    $request->validate([
        'name'          => 'required|string|max:255',
        'email'         => 'required|email|unique:users,email,' . $id,
        'specialization'=> 'required|string|max:255',
        'cv'            => 'nullable|mimes:pdf|max:2048',
        'profile_photo' => 'nullable|image|max:2048',
    ]);

    if ($request->hasFile('cv')) {
        if ($emp->cv && file_exists(public_path('uploads/cv/' . $emp->cv))) {
            unlink(public_path('uploads/cv/' . $emp->cv));
        }
        $cvFileName = time() . '_' . $request->cv->getClientOriginalName();
        $request->cv->move(public_path('uploads/cv'), $cvFileName);
        $emp->cv = $cvFileName;
    }

    if ($request->hasFile('profile_photo')) {
        if ($emp->profile_photo && file_exists(public_path('uploads/photos/' . $emp->profile_photo))) {
            unlink(public_path('uploads/photos/' . $emp->profile_photo));
        }
        $photoFileName = time() . '_' . $request->profile_photo->getClientOriginalName();
        $request->profile_photo->move(public_path('uploads/photos'), $photoFileName);
        $emp->profile_photo = $photoFileName;
    }

    $emp->name           = $request->name;
    $emp->email          = $request->email;
    $emp->specialization = $request->specialization;
    $emp->save();

    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'updated_employee',
            'target'     => $emp->name,
            'details'    => 'Updated employee data',
            'ip_address' => $request->ip(),
        ]);
    } catch (\Exception $e) {}

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
                Route::get('/run-migrate', function (Request $request) {
    try {
        // تنظيف الكاش بالكامل لضمان قراءة التنسيقات والمتغيرات الجديدة
        \Artisan::call('config:clear');
        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');
        \Artisan::call('route:clear');
        
        if ($request->query('fresh') === 'true') {
            \Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            \Artisan::call('migrate', ['--force' => true]);
        }
        
        // إنشاء حساب الآدمن الافتراضي لجامعة الأفضل
        $adminEmail = 'admin@aftal.edu.ly';
        if (!\App\Models\User::where('email', $adminEmail)->exists()) {
            \App\Models\User::create([
                'name' => 'إدارة جامعة الأفضل الدولية',
                'email' => $adminEmail,
                'specialization' => 'System Admin',
                'role' => 'admin',
                'password' => \Illuminate\Support\Facades\Hash::make('Aftal@2026')
            ]);
        }

        // تحويل مباشر لصفحة الدخول بعد النجاح بدلاً من العرض النصي لضمان رؤية الواجهة فوراً
        return redirect()->route('login')->with('success', 'تم تهيئة النظام وقاعدة البيانات بنجاح!');

    } catch (\Exception $e) {
        return '<div style="padding:20px; font-family:monospace; background:#fadbd8; color:#78281f; border:1px solid #f5b7b1; direction:ltr; text-align:left;">
                    <h3>Error during sync:</h3>
                    <p>' . nl2br(e($e->getMessage())) . '</p>
                </div>';
    }
});
    }
});

/*
|--------------------------------------------------------------------------
| 5. الميزات الجديدة: التقارير، التقويم، الإشعارات، سجل النشاط
|--------------------------------------------------------------------------
*/

// PDF Monthly Report
Route::get('/reports/monthly', [ReportController::class, 'monthly'])->middleware('auth');

// Attendance Calendar
Route::get('/attendance/calendar', function (Request $request) {
    if (Auth::user()->role !== 'admin') abort(403);

    $employees     = User::where('role', 'employee')->get();
    $selectedMonth = $request->get('month', now()->format('Y-m'));
    $employeeId    = $request->get('employee_id');

    $startOfMonth = Carbon::parse($selectedMonth)->startOfMonth();
    $endOfMonth   = Carbon::parse($selectedMonth)->endOfMonth();
    $startOffset  = $startOfMonth->dayOfWeek; // 0=Sun

    $attendanceQuery = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth]);
    if ($employeeId) {
        $attendanceQuery->where('employee_id', $employeeId);
    }
    $attendanceRecords = $attendanceQuery->get()->keyBy(fn($a) => Carbon::parse($a->date)->format('Y-m-d'));

    $days = [];
    for ($d = 1; $d <= $endOfMonth->day; $d++) {
        $dateStr = $startOfMonth->copy()->day($d)->format('Y-m-d');
        $record  = $attendanceRecords[$dateStr] ?? null;
        $days[]  = [
            'date'    => $d,
            'present' => $record !== null,
            'absent'  => false,
            'hours'   => $record ? $record->hours_worked : 0,
        ];
    }

    return view('attendance.calendar', compact('employees', 'days', 'selectedMonth', 'startOffset'));
})->middleware('auth');

// Mark all notifications as read
Route::get('/notifications/mark-read', function () {
    if (Auth::user()->isAdmin()) {
        try {
            AppNotification::where('is_read', false)->update(['is_read' => true]);
        } catch (\Exception $e) {}
    }
    return redirect()->back();
})->middleware('auth');

// Activity Log page
Route::get('/activity-log', function () {
    if (Auth::user()->role !== 'admin') abort(403);
    try {
        $logs = ActivityLog::with('user')->latest()->paginate(30);
    } catch (\Exception $e) {
        $logs = collect();
    }
    return view('admin.activity-log', compact('logs'));
})->middleware('auth');