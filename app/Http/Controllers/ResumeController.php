<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function view($id)
    {
        $employee = \App\Models\User::findOrFail($id);
        if (!$employee->cv) {
            abort(404, 'Resume not found.');
        }

        $path = public_path('uploads/cv/' . $employee->cv);
        if (!file_exists($path)) {
            abort(404, 'Resume file not found on disk.');
        }

        return response()->file($path);
    }

    public function download($id)
    {
        $employee = \App\Models\User::findOrFail($id);
        if (!$employee->cv) {
            abort(404, 'Resume not found.');
        }

        $path = public_path('uploads/cv/' . $employee->cv);
        if (!file_exists($path)) {
            abort(404, 'Resume file not found on disk.');
        }

        return response()->download($path, 'Resume_' . $employee->name . '.pdf');
    }
}
