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

// تغيير كلمة المرور (لكل المستخدمين)
Route::get('/change-password', function () {
    return view('auth.change-password');
})->middleware('auth');

Route::post('/change-password', function (Request $request) {
    $request->validate([
        'current_password' => 'required|current_password',
        'password'         => 'required|string|min:6|confirmed|different:current_password',
    ], [
        'current_password.required'         => 'أدخل كلمة المرور الحالية',
        'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة',
        'password.required'                 => 'أدخل كلمة المرور الجديدة',
        'password.min'                      => 'كلمة المرور الجديدة يجب أن تكون 6 أحرف أو أرقام على الأقل',
        'password.confirmed'                => 'تأكيد كلمة المرور غير مطابق',
        'password.different'                => 'كلمة المرور الجديدة يجب أن تختلف عن الحالية',
    ]);

    $user = Auth::user();
    $user->password = Hash::make($request->password);
    if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'must_change_password')) {
        $user->must_change_password = false;
    }
    $user->save();

    $request->session()->regenerate();

    try {
        ActivityLog::create([
            'user_id'    => $user->id,
            'action'     => 'changed_password',
            'target'     => $user->name,
            'details'    => 'Password changed by user',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    } catch (\Exception $e) {}

    return back()->with('success', 'تم تغيير كلمة المرور بنجاح ✅');
})->middleware('auth');

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

    $employees    = User::where('role', 'employee')->get();
    $totalEmployees = $employees->count();

    try { $totalHours = Attendance::sum('hours_worked') ?? 0; } catch (\Exception $e) { $totalHours = 0; }
    try { $totalSalaries = Finance::sum('salary') ?? 0; } catch (\Exception $e) { $totalSalaries = 0; }

    $totalDepartments = User::where('role', 'employee')->distinct('specialization')->count('specialization');
    $specializations  = User::where('role', 'employee')->whereNotNull('specialization')->distinct()->pluck('specialization');

    \Log::info('Dashboard loaded: employees=' . $totalEmployees . ' | user=' . Auth::id());

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

// إضافة مشرف جديد
Route::post('/add-supervisor', function (Request $request) {
    if (Auth::user()->role !== 'admin') abort(403);

    $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
    ]);

    $supervisor = User::create([
        'name'        => $request->name,
        'email'       => $request->email,
        'password'    => Hash::make($request->password),
        'role'        => 'supervisor',
        'permissions' => json_encode($request->permissions ?? []),
    ]);

    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'added_supervisor',
            'target'     => $supervisor->name,
            'details'    => 'Permissions: ' . implode(', ', $request->permissions ?? ['none']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    } catch (\Exception $e) {}

    return redirect('/')->with('success', 'تم إضافة المشرف بنجاح: ' . $supervisor->name);
})->middleware('auth');

// صفحة إضافة موظف جديد
Route::get('/create-employee', function () {
    if (Illuminate\Support\Facades\Auth::user()->role !== 'admin') abort(403);
    return view('admin.create_employee');
})->middleware('auth');

// معالجة إضافة الموظف (مع التشفير ورفع الملف)
Route::post('/add-employee', function (Request $request) {
    \Log::info('ADD EMPLOYEE HIT', $request->except(['password', 'cv', 'profile_photo']));

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
        $dir = public_path('uploads/cv');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $cvFileName = time() . '_' . $request->cv->getClientOriginalName();
        $request->cv->move($dir, $cvFileName);
    }

    if ($request->hasFile('profile_photo')) {
        $dir = public_path('uploads/photos');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $photoFileName = time() . '_' . $request->profile_photo->getClientOriginalName();
        $request->profile_photo->move($dir, $photoFileName);
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
        $employee->forceFill(['must_change_password' => true])->save();
    } catch (\Exception $e) {
        \Log::error('Employee create failed: ' . $e->getMessage());
        return back()->withErrors(['error' => 'فشل إضافة الموظف: ' . $e->getMessage()])->withInput();
    }

    \Log::info('Employee created: ID=' . $employee->id . ' Name=' . $employee->name);

    try {
        AppNotification::create([
            'title'   => 'موظف جديد',
            'message' => 'تم إضافة موظف جديد: ' . $employee->name,
            'is_read' => false,
        ]);
    } catch (\Exception $e) {}

    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'added_employee',
            'target'     => $employee->name,
            'details'    => 'Added new employee with email: ' . $employee->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    } catch (\Exception $e) {}

    return redirect('/')->with('success', 'تم إضافة الموظف بنجاح: ' . $employee->name);
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
            'user_agent' => $request->userAgent(),
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
    // مسموح فقط: قبل وجود أي آدمن (أول تشغيل)، أو للآدمن المسجّل دخوله
    $noAdminYet = false;
    try { $noAdminYet = !User::where('role', 'admin')->exists(); } catch (\Exception $e) { $noAdminYet = true; }
    if (!$noAdminYet && !(Auth::check() && Auth::user()->role === 'admin')) {
        abort(403);
    }

    try {
        \Artisan::call('migrate', ['--force' => true]);
        $output = \Artisan::output();

        $adminEmail = 'admin@aftal.edu.ly';
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name'           => 'إدارة جامعة الأفضل الدولية',
                'email'          => $adminEmail,
                'specialization' => 'System Admin',
                'role'           => 'admin',
                'password'       => Hash::make(env('ADMIN_INITIAL_PASSWORD', \Illuminate\Support\Str::random(16))),
            ]);
        }

        return '<div style="font-family:sans-serif;direction:rtl;max-width:600px;margin:50px auto;padding:20px;border:1px solid #d6dbdf;border-radius:10px;">'
             . '<h2 style="color:#16a34a;">✅ تم تحديث قاعدة البيانات</h2>'
             . '<pre style="direction:ltr;text-align:left;background:#f8fafc;padding:10px;white-space:pre-wrap;">' . e($output) . '</pre>'
             . '<a href="' . url('/') . '">العودة للوحة التحكم</a></div>';
    } catch (\Exception $e) {
        \Log::error('run-migrate failed: ' . $e->getMessage());
        return response('<pre style="direction:ltr">Migration error: ' . e($e->getMessage()) . '</pre>', 500);
    }
});

/*
|--------------------------------------------------------------------------
| 5. الميزات الجديدة: التقارير، التقويم، الإشعارات، سجل النشاط
|--------------------------------------------------------------------------
*/

// PDF Monthly Report
Route::get('/reports/monthly', [ReportController::class, 'monthly'])->middleware('auth');

// GPS Check-in (حضور)
Route::post('/attendance/check-in', function (Request $request) {
    $request->validate([
        'employee_id' => 'required|exists:users,id',
        'latitude'    => 'required|numeric|between:-90,90',
        'longitude'   => 'required|numeric|between:-180,180',
        'device_id'   => 'nullable|string|max:64',
    ]);

    $empId    = (int) $request->employee_id;
    $isSelf   = (int) Auth::id() === $empId;
    if (!$isSelf && Auth::user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
    }
    $employee = User::findOrFail($empId);

    // Device binding applies when the employee records for themselves
    if ($isSelf && $employee->role === 'employee') {
        if ($err = \App\Support\AttendanceGuard::checkDevice($request, $employee)) {
            return response()->json(['success' => false, 'message' => $err], 403);
        }
    }

    $today = now()->toDateString();
    if (Attendance::where('employee_id', $empId)->whereDate('date', $today)->exists()) {
        return response()->json(['success' => false, 'message' => 'تم تسجيل حضورك مسبقاً اليوم']);
    }

    $distance = \App\Support\AttendanceGuard::distance((float) $request->latitude, (float) $request->longitude);
    $verified = $distance <= \App\Support\AttendanceGuard::MAX_DISTANCE;
    $now      = now();

    try {
        Attendance::create([
            'employee_id'       => $empId,
            'date'              => $today,
            'hours_worked'      => 0,
            'status'            => 'present',
            'check_in_time'     => $now->format('H:i:s'),
            'latitude'          => $request->latitude,
            'longitude'         => $request->longitude,
            'distance_meters'   => round($distance, 2),
            'location_verified' => $verified,
            'ip_address'        => $request->ip(),
            'device_id'         => $request->device_id,
        ]);
    } catch (\Exception $e) {
        \Log::error('Attendance::create failed: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'خطأ في قاعدة البيانات'], 500);
    }

    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'check_in',
            'target'     => $employee->name,
            'details'    => 'Distance: ' . round($distance) . 'm | Verified: ' . ($verified ? 'Yes' : 'No'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    } catch (\Exception $e) {}

    return response()->json([
        'success'  => true,
        'message'  => 'تم تسجيل الحضور بنجاح',
        'distance' => round($distance),
        'verified' => $verified,
        'date'     => $now->format('d/m/Y'),
        'time'     => $now->format('H:i'),
    ]);
})->middleware('auth');

// GPS Check-out (انصراف)
Route::post('/attendance/check-out', function (Request $request) {
    $request->validate([
        'employee_id' => 'required|exists:users,id',
        'latitude'    => 'required|numeric|between:-90,90',
        'longitude'   => 'required|numeric|between:-180,180',
        'device_id'   => 'nullable|string|max:64',
    ]);

    $empId  = (int) $request->employee_id;
    $isSelf = (int) Auth::id() === $empId;
    if (!$isSelf && Auth::user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
    }
    $employee = User::findOrFail($empId);

    if ($isSelf && $employee->role === 'employee') {
        if ($err = \App\Support\AttendanceGuard::checkDevice($request, $employee)) {
            return response()->json(['success' => false, 'message' => $err], 403);
        }
    }

    $att = Attendance::where('employee_id', $empId)->whereDate('date', now()->toDateString())->first();
    if (!$att) {
        return response()->json(['success' => false, 'message' => 'لم يتم تسجيل حضورك اليوم، سجّل الحضور أولاً']);
    }
    if ($att->check_out_time) {
        return response()->json(['success' => false, 'message' => 'تم تسجيل انصرافك مسبقاً اليوم']);
    }

    $distance = \App\Support\AttendanceGuard::distance((float) $request->latitude, (float) $request->longitude);
    $verified = $distance <= \App\Support\AttendanceGuard::MAX_DISTANCE;
    $now      = now();

    $checkIn = Carbon::parse($att->date)->setTimeFromTimeString($att->check_in_time ?? $now->format('H:i:s'));
    $hours   = max(0, round($checkIn->diffInMinutes($now) / 60, 2));

    $att->update([
        'check_out_time'            => $now->format('H:i:s'),
        'check_out_latitude'        => $request->latitude,
        'check_out_longitude'       => $request->longitude,
        'check_out_distance_meters' => round($distance, 2),
        'check_out_verified'        => $verified,
        'hours_worked'              => $hours,
    ]);

    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'check_out',
            'target'     => $employee->name,
            'details'    => 'Distance: ' . round($distance) . 'm | Verified: ' . ($verified ? 'Yes' : 'No') . ' | Hours: ' . $hours,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    } catch (\Exception $e) {}

    return response()->json([
        'success'  => true,
        'message'  => 'تم تسجيل الانصراف بنجاح',
        'distance' => round($distance),
        'verified' => $verified,
        'date'     => $now->format('d/m/Y'),
        'time'     => $now->format('H:i'),
        'hours'    => $hours,
    ]);
})->middleware('auth');

// إعادة تعيين الهاتف المسجّل للموظف (Admin فقط) - عند تغيير الموظف لهاتفه
Route::post('/admin/employees/{id}/reset-device', function (Request $request, $id) {
    if (Auth::user()->role !== 'admin') abort(403);
    $emp = User::findOrFail($id);
    $emp->forceFill(['device_id' => null, 'device_bound_at' => null])->save();
    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => 'reset_device',
            'target'     => $emp->name,
            'details'    => 'Phone binding cleared',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    } catch (\Exception $e) {}
    return back()->with('success', 'تم إعادة تعيين الهاتف. سيُربط الحساب بأول هاتف يسجّل منه الموظف');
})->middleware('auth');

// Attendance Detail (admin)
Route::get('/attendance/detail/{id}', function ($id) {
    if (Auth::user()->role !== 'admin') abort(403);
    $att = Attendance::with('employee')->findOrFail($id);
    return response()->json([
        'name'     => $att->employee->name,
        'date'     => Carbon::parse($att->date)->format('d/m/Y'),
        'time'     => $att->check_in_time,
        'check_out_time'     => $att->check_out_time,
        'check_out_distance' => $att->check_out_distance_meters !== null ? round($att->check_out_distance_meters) : null,
        'check_out_verified' => (bool) $att->check_out_verified,
        'hours'    => $att->hours_worked,
        'distance' => round($att->distance_meters ?? 0),
        'verified' => (bool) $att->location_verified,
        'lat'      => $att->latitude,
        'lng'      => $att->longitude,
    ]);
})->middleware('auth');

// Attendance Calendar
Route::get('/attendance/calendar', function (Request $request) {
    if (Auth::user()->role !== 'admin') abort(403);

    $employees     = User::where('role', 'employee')->get();
    $selectedMonth = $request->get('month', now()->format('Y-m'));
    $employeeId    = $request->get('employee_id');

    $startOfMonth = Carbon::parse($selectedMonth)->startOfMonth();
    $endOfMonth   = Carbon::parse($selectedMonth)->endOfMonth();
    $startOffset  = $startOfMonth->dayOfWeek; // 0=Sun

    $attendanceQuery = Attendance::with('employee')
        ->whereBetween('date', [$startOfMonth, $endOfMonth]);
    if ($employeeId) {
        $attendanceQuery->where('employee_id', $employeeId);
    }
    $allAttendances    = $attendanceQuery->get();
    $attendanceRecords = $allAttendances->keyBy(fn($a) => Carbon::parse($a->date)->format('Y-m-d'));

    // Group by date for multi-employee view
    $attendancesByDate = $allAttendances->groupBy(fn($a) => Carbon::parse($a->date)->format('Y-m-d'));

    $days = [];
    for ($d = 1; $d <= $endOfMonth->day; $d++) {
        $dateStr = $startOfMonth->copy()->day($d)->format('Y-m-d');
        $record  = $attendanceRecords[$dateStr] ?? null;
        $days[]  = [
            'date'    => $d,
            'dateStr' => $dateStr,
            'present' => $record !== null,
            'absent'  => false,
            'hours'   => $record ? $record->hours_worked : 0,
        ];
    }

    return view('attendance.calendar', compact('employees', 'days', 'selectedMonth', 'startOffset', 'attendancesByDate'));
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
        $logs = ActivityLog::with('user')->latest()->paginate(50);
        $sessionSummary = ActivityLog::with('user')
            ->selectRaw('user_id, DATE(created_at) as date,
                         COUNT(*) as total_actions,
                         SUM(action = "added_employee") as added,
                         SUM(action LIKE "edit%") as edited,
                         SUM(action LIKE "delete%") as deleted')
            ->groupBy('user_id', 'date')
            ->orderBy('date', 'desc')
            ->get();
    } catch (\Exception $e) {
        $logs = collect();
        $sessionSummary = collect();
    }
    return view('admin.activity-log', compact('logs', 'sessionSummary'));
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| 6. المحفظة المالية (القسائم)
|--------------------------------------------------------------------------
*/

Route::post('/voucher/add', function (Request $request) {
    if (!Auth::user()->isAdmin()) abort(403);

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'employee_id' => 'required|exists:users,id',
        'amount'      => 'required|numeric|min:0.01|max:99999999',
        'description' => 'nullable|string|max:255',
        'status'      => 'nullable|in:locked,available',
        'attachment'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,heic,heif|max:10240',
    ], [
        'amount.required'  => 'أدخل مبلغ القسيمة',
        'amount.numeric'   => 'المبلغ يجب أن يكون رقماً',
        'amount.min'       => 'المبلغ يجب أن يكون أكبر من صفر',
        'description.max'  => 'الوصف طويل جداً (255 حرفاً كحد أقصى)',
        'attachment.mimes' => 'المرفق يجب أن يكون PDF أو صورة (jpg, png, webp, heic)',
        'attachment.max'   => 'حجم المرفق يجب ألا يتجاوز 10 ميجابايت',
        'attachment.uploaded' => 'فشل رفع المرفق، قد يكون حجمه كبيراً جداً',
    ]);

    if ($validator->fails()) {
        return back()->withErrors($validator, 'voucher')->withInput()->with('voucher_error', true);
    }

    try {
        $file = null;
        if ($request->hasFile('attachment')) {
            $dir = public_path('uploads/vouchers');
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $ext  = strtolower($request->attachment->getClientOriginalExtension() ?: 'bin');
            $file = time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $ext;
            $request->attachment->move($dir, $file);
        }

        \App\Models\EmployeeVoucher::create([
            'employee_id' => $request->employee_id,
            'amount'      => $request->amount,
            'description' => $request->description,
            'attachment'  => $file,
            'status'      => $request->status ?: 'locked',
            'created_by'  => Auth::id(),
        ]);
    } catch (\Exception $e) {
        \Log::error('Voucher create failed: ' . $e->getMessage());
        return back()->withErrors(['attachment' => 'تعذر حفظ القسيمة، حاول مرة أخرى'], 'voucher')
                     ->withInput()->with('voucher_error', true);
    }

    return back()->with('voucher_success', 'تم إضافة القسيمة بنجاح ✅');
})->middleware('auth');

Route::patch('/voucher/{id}/unlock', function ($id) {
    if (!Auth::user()->isAdmin()) abort(403);
    \App\Models\EmployeeVoucher::findOrFail($id)->update([
        'status'      => 'available',
        'unlocked_at' => now(),
    ]);
    return back()->with('voucher_success', 'تم فتح القسيمة ✅');
})->middleware('auth');

Route::get('/voucher/{id}/download', function ($id) {
    $v = \App\Models\EmployeeVoucher::findOrFail($id);
    if ((int) Auth::id() !== (int) $v->employee_id && Auth::user()->role !== 'admin') abort(403);
    if ($v->status !== 'available' && Auth::user()->role !== 'admin') abort(403);
    if (!$v->attachment) abort(404);
    $path = public_path('uploads/vouchers/' . $v->attachment);
    if (!file_exists($path)) abort(404);
    return response()->download($path);
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| 7. رفع السيرة الذاتية من قِبل الموظف
|--------------------------------------------------------------------------
*/

Route::post('/employee/upload-cv', function (Request $request) {
    $request->validate(['cv' => 'required|mimes:pdf|max:5120']);
    $dir = public_path('uploads/cv');
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $fileName = time() . '_' . $request->cv->getClientOriginalName();
    $request->cv->move($dir, $fileName);
    Auth::user()->update(['cv' => $fileName]);
    return back()->with('success', 'تم رفع السيرة الذاتية بنجاح');
})->middleware('auth')->name('employee.upload-cv');