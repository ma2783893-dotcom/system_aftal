<?php

/*
|--------------------------------------------------------------------------
| 12. الطلبات (إجازة / إذن / نسيت كلمة المرور) + النسخة الاحتياطية + كاشف التلاعب
|--------------------------------------------------------------------------
*/

use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\StaffRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

$logAction = function (Request $request, string $action, string $target, string $details = '') {
    try {
        ActivityLog::create([
            'user_id'    => Auth::id(),
            'action'     => $action,
            'target'     => $target,
            'details'    => $details,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    } catch (\Exception $e) {}
};

$notifyAdmin = function (string $title, string $message) {
    try {
        AppNotification::create(['title' => $title, 'message' => $message, 'is_read' => false]);
    } catch (\Exception $e) {}
};

$adminOnly = function () {
    if (!Auth::user()->isAdmin()) abort(403);
};

/* ---------- نسيت كلمة المرور (من شاشة الدخول) ---------- */

Route::get('/forgot-password', function () {
    if (Auth::check()) return redirect('/');
    return view('auth.forgot');
});

Route::post('/forgot-password', function (Request $request) use ($notifyAdmin) {
    $data = $request->validate([
        'email'  => 'required|email|max:190',
        'reason' => 'nullable|string|max:300',
    ], ['email.*' => 'اكتب بريدك الإلكتروني بشكل صحيح']);

    $done = 'وصل طلبك للإدارة ✅ ستُعطى كلمة مرور جديدة بعد المراجعة، تواصل مع الإدارة إذا تأخر الرد.';
    if (!StaffRequest::ensureTable()) {
        return back()->with('flash_error', 'الخدمة غير متاحة الآن، تواصل مع الإدارة مباشرة.');
    }

    $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))])->first();
    // the same reply whether or not the e-mail exists, so the form cannot be used to guess accounts
    if ($user) {
        $recent = StaffRequest::where('user_id', $user->id)->where('type', 'password')
            ->where('status', 'pending')->exists();
        if (!$recent) {
            StaffRequest::create([
                'user_id'    => $user->id,
                'type'       => 'password',
                'email'      => $data['email'],
                'reason'     => $data['reason'] ?? null,
                'ip_address' => $request->ip(),
            ]);
            $notifyAdmin('طلب كلمة مرور', $user->name . ' نسي كلمة المرور ويطلب كلمة جديدة');
        }
    }
    return redirect('/login')->with('success', $done);
})->middleware('throttle:5,10');

/* ---------- طلبات الموظف (إجازة / إذن) ---------- */

Route::post('/requests', function (Request $request) use ($notifyAdmin, $logAction) {
    $user = Auth::user();
    $data = $request->validate([
        'type'      => 'required|in:leave,late,early',
        'date_from' => 'required|date|after_or_equal:' . now()->subDays(7)->toDateString() . '|before_or_equal:' . now()->addDays(120)->toDateString(),
        'date_to'   => 'nullable|date|after_or_equal:date_from',
        'time'      => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
        'reason'    => 'required|string|max:500',
    ], [
        'type.*'                    => 'اختر نوع الطلب',
        'date_from.required'        => 'اختر التاريخ',
        'date_from.after_or_equal'  => 'لا يمكن الطلب لتاريخ مضى عليه أكثر من أسبوع',
        'date_from.before_or_equal' => 'التاريخ بعيد جداً',
        'date_to.after_or_equal'    => 'تاريخ النهاية يجب أن يكون بعد البداية',
        'time.regex'                => 'الوقت غير صحيح',
        'reason.required'           => 'اكتب سبب الطلب',
    ]);

    if (in_array($data['type'], ['late', 'early']) && empty($data['time'])) {
        return back()->withInput()->withErrors(['time' => 'حدد الساعة'], 'req')->withFragment('requests');
    }
    if ($data['type'] === 'leave') {
        $to = Carbon::parse($data['date_to'] ?? $data['date_from']);
        if (Carbon::parse($data['date_from'])->diffInDays($to) > 30) {
            return back()->withInput()->withErrors(['date_to' => 'أقصى مدة للطلب الواحد 31 يوماً'], 'req')->withFragment('requests');
        }
    }
    if (!StaffRequest::ensureTable()) {
        return back()->with('flash_error', 'تعذّر حفظ الطلب الآن، حاول لاحقاً.');
    }

    $req = StaffRequest::create([
        'user_id'    => $user->id,
        'type'       => $data['type'],
        'date_from'  => $data['date_from'],
        'date_to'    => $data['type'] === 'leave' ? ($data['date_to'] ?? $data['date_from']) : $data['date_from'],
        'time'       => $data['type'] === 'leave' ? null : $data['time'],
        'reason'     => $data['reason'],
        'ip_address' => $request->ip(),
    ]);
    $notifyAdmin('طلب ' . $req->typeLabel(), $user->name . ': ' . $req->summary());
    $logAction($request, 'staff_request', $user->name, $req->typeLabel() . ' — ' . $req->summary());

    return back()->with('req_success', 'تم إرسال طلبك للإدارة ✅')->withFragment('requests');
})->middleware('auth');

Route::post('/requests/{id}/cancel', function ($id) {
    $req = StaffRequest::where('id', $id)->where('user_id', Auth::id())->where('status', 'pending')->firstOrFail();
    $req->delete();
    return back()->with('req_success', 'تم إلغاء الطلب')->withFragment('requests');
})->middleware('auth');

/* ---------- صفحة الطلبات للمدير ---------- */

Route::get('/admin/requests', function (Request $request) use ($adminOnly) {
    $adminOnly();
    StaffRequest::ensureTable();
    $status = in_array($request->input('status'), ['pending', 'approved', 'rejected', 'all']) ? $request->input('status') : 'pending';
    $q = StaffRequest::with('user')->orderByDesc('created_at');
    if ($status !== 'all') $q->where('status', $status);
    return view('admin.requests', [
        'requests' => $q->limit(300)->get(),
        'status'   => $status,
        'counts'   => StaffRequest::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
    ]);
})->middleware('auth');

Route::post('/admin/requests/{id}', function (Request $request, $id) use ($adminOnly, $logAction) {
    $adminOnly();
    $req = StaffRequest::with('user')->findOrFail($id);
    $data = $request->validate([
        'decision'   => 'required|in:approve,reject',
        'admin_note' => 'nullable|string|max:500',
    ]);
    if ($req->status !== 'pending') {
        return back()->with('flash_error', 'تم الرد على هذا الطلب مسبقاً');
    }
    $user = $req->user;
    if (!$user) {
        $req->update(['status' => 'rejected', 'admin_note' => 'الحساب غير موجود', 'decided_by' => Auth::id(), 'decided_at' => now()]);
        return back()->with('flash_error', 'صاحب الطلب لم يعد موجوداً');
    }

    if ($data['decision'] === 'reject') {
        $req->update(['status' => 'rejected', 'admin_note' => $data['admin_note'] ?? null, 'decided_by' => Auth::id(), 'decided_at' => now()]);
        $logAction($request, 'request_rejected', $user->name, $req->typeLabel() . ' — ' . $req->summary());
        return back()->with('success', 'تم رفض الطلب');
    }

    $flash = ['success' => 'تمت الموافقة ✅'];

    if ($req->type === 'password') {
        $plain = (string) random_int(1000000, 9999999);       // 7 digits: easy to type on a phone
        $user->forceFill(['password' => Hash::make($plain), 'must_change_password' => false])->save();
        $flash['new_password'] = ['name' => $user->name, 'email' => $user->email, 'password' => $plain];
    } else {
        $from  = Carbon::parse($req->date_from);
        $to    = Carbon::parse($req->date_to ?? $req->date_from);
        $label = match ($req->type) {
            'leave' => 'إجازة معتمدة',
            'late'  => 'إذن تأخير معتمد حتى ' . $req->time,
            'early' => 'إذن خروج مبكر معتمد الساعة ' . $req->time,
        };
        $note = mb_substr($label . ' — ' . $req->reason, 0, 500);
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $att = Attendance::where('employee_id', $user->id)->whereDate('date', $day->toDateString())->first();
            if (!$att) {
                // a note-only day; if the employee checks in later the note is kept
                $att = new Attendance([
                    'employee_id'       => $user->id,
                    'date'              => $day->toDateString(),
                    'hours_worked'      => 0,
                    'status'            => 'absent',
                    'location_verified' => true,
                ]);
            }
            $att->note      = $att->note && !str_contains($att->note, $label) ? mb_substr($att->note . ' | ' . $note, 0, 500) : $note;
            $att->manual    = true;
            $att->edited_by = Auth::id();
            $att->save();
        }
    }

    $req->update(['status' => 'approved', 'admin_note' => $data['admin_note'] ?? null, 'decided_by' => Auth::id(), 'decided_at' => now()]);
    $logAction($request, 'request_approved', $user->name, $req->typeLabel() . ' — ' . $req->summary());

    return back()->with($flash);
})->middleware('auth');

/* ---------- النسخة الاحتياطية ---------- */

Route::get('/admin/backup', function () use ($adminOnly) {
    $adminOnly();
    return view('admin.backup', [
        'stats' => \App\Support\Backup::stats(),
        'last'  => \App\Support\Settings::get('backup.last'),
    ]);
})->middleware('auth');

Route::post('/admin/backup', function (Request $request) use ($adminOnly, $logAction) {
    $adminOnly();
    @set_time_limit(600);
    $withFiles = $request->boolean('files');
    \App\Support\Settings::set('backup.last', now()->format('Y-m-d H:i'));
    $logAction($request, 'backup_download', 'قاعدة البيانات', $withFiles ? 'with files' : 'data only');

    $name = 'aftal-backup-' . now()->format('Y-m-d_H-i') . ($withFiles ? '-full' : '') . '.sql';
    return response()->streamDownload(function () use ($withFiles) {
        \App\Support\Backup::write($withFiles, function (string $chunk) {
            echo $chunk;
            if (ob_get_level() > 0) @ob_flush();
            flush();
        });
    }, $name, ['Content-Type' => 'application/sql; charset=utf-8', 'Cache-Control' => 'no-store']);
})->middleware('auth');

/* ---------- كاشف التلاعب ---------- */

Route::get('/admin/anomalies', function (Request $request) use ($adminOnly) {
    $adminOnly();
    $days = in_array((int) $request->input('days'), [7, 30, 90]) ? (int) $request->input('days') : 30;
    return view('admin.anomalies', \App\Support\Anomalies::scan($days));
})->middleware('auth');
