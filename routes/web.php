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
| 2. روابط الدخول والخروج (الحل للمشكلة السابقة)
|--------------------------------------------------------------------------
*/

// عرض صفحة الدخول
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// عملية الدخول الفعلية (هذا هو السطر الذي كان ينقصك)
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

// تعديل الموظف (عرض الصفحة)
Route::get('/edit-employee/{id}', function ($id) {
    if (Illuminate\Support\Facades\Auth::user()->role !== 'admin') abort(403);
    $emp = User::findOrFail($id);
    return view('admin.edit', compact('emp'));
})->middleware('auth');

// تحديث بيانات الموظف
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
        // حذف القديم أولاً
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

    return redirect('/')->with('success', 'تم تحديث البيانات');
})->middleware('auth');
