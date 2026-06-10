<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function view($id)
    {
        if (!auth()->check()) abort(403);

        $employee = \App\Models\User::findOrFail($id);

        if (!$employee->cv) {
            return back()->with('error', 'لا يوجد ملف سيرة ذاتية لهذا الموظف');
        }

        $path = public_path('uploads/cv/' . $employee->cv);

        if (!file_exists($path)) {
            return back()->with('error', 'الملف غير موجود على السيرفر');
        }

        return response()->file($path);
    }

    public function download($id)
    {
        if (!auth()->check()) abort(403);

        $employee = \App\Models\User::findOrFail($id);

        if (!$employee->cv) {
            return back()->with('error', 'لا يوجد ملف سيرة ذاتية لهذا الموظف');
        }

        $path = public_path('uploads/cv/' . $employee->cv);

        if (!file_exists($path)) {
            return back()->with('error', 'الملف غير موجود على السيرفر — قد يكون حُذف أو لم يُرفع بعد');
        }

        return response()->download($path, 'Resume_' . $employee->name . '.pdf');
    }
}
