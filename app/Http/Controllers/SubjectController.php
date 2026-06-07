<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Subject;
use App\Models\User;

class SubjectController extends Controller
{
    /**
     * Store a new subject for an employee (Admin only).
     */
    public function store(Request $request, $employeeId)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'غير مصرح لك بهذا الإجراء.');
        }

        $request->validate([
            'subject_name' => 'required|string|max:255',
            'hourly_rate'  => 'required|numeric|min:0',
            'worked_hours' => 'required|numeric|min:0',
        ], [
            'subject_name.required' => 'اسم المادة مطلوب.',
            'subject_name.max'      => 'اسم المادة يجب ألا يتجاوز 255 حرفاً.',
            'hourly_rate.required'  => 'السعر بالساعة مطلوب.',
            'hourly_rate.numeric'   => 'السعر بالساعة يجب أن يكون رقماً.',
            'hourly_rate.min'       => 'السعر بالساعة يجب أن يكون 0 أو أكثر.',
            'worked_hours.required' => 'ساعات العمل مطلوبة.',
            'worked_hours.numeric'  => 'ساعات العمل يجب أن تكون رقماً.',
            'worked_hours.min'      => 'ساعات العمل يجب أن تكون 0 أو أكثر.',
        ]);

        // Ensure the employee exists
        User::findOrFail($employeeId);

        Subject::create([
            'employee_id'  => $employeeId,
            'subject_name' => $request->subject_name,
            'hourly_rate'  => $request->hourly_rate,
            'worked_hours' => $request->worked_hours,
            // total_amount is computed automatically via the model's booted() hook
        ]);

        return back()->with('subject_success', 'تم إضافة المادة بنجاح.');
    }

    /**
     * Delete a subject (Admin only).
     */
    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'غير مصرح لك بهذا الإجراء.');
        }

        $subject = Subject::findOrFail($id);
        $subject->delete();

        return back()->with('subject_success', 'تم حذف المادة بنجاح.');
    }
}
