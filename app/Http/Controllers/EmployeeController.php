<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function dashboard()
    {
        $employee = Auth::user();
        return view('employee.dashboard', compact('employee'));
    }

    public function uploadCv(Request $request)
    {
        $request->validate([
            'cv' => 'required|mimes:pdf|max:2048',
        ]);

        $path = $request->file('cv')->store('cvs', 'public');
        
        $employee = Auth::user();
        $employee->cv_file = $path;
        $employee->save();

        return back()->with('success', 'تم رفع السيرة الذاتية بنجاح.');
    }
}
