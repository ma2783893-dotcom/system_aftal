<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\FileStore;

class ResumeController extends Controller
{
    public function view($id)
    {
        return $this->serve($id, true);
    }

    public function download($id)
    {
        return $this->serve($id, false);
    }

    private function serve($id, bool $inline)
    {
        $employee = User::findOrFail($id);
        if (!auth()->user()->canSeeFilesOf($employee)) {
            abort(403);
        }

        $name = 'السيرة الذاتية - ' . $employee->name . '.' . FileStore::extension($employee->cv, 'pdf');
        // Word files and HEIC photos can't be shown by most browsers: send them as a download.
        $inline   = $inline && FileStore::viewable($employee->cv);
        $response = FileStore::response($employee->cv, 'cv', $name, $inline);

        if ($response) {
            return $response;
        }

        // The file was lost (old uploads were wiped by a redeploy) or never uploaded.
        return response()->view('errors.file-missing', [
            'title'   => $employee->cv ? 'ملف السيرة الذاتية مفقود' : 'لا توجد سيرة ذاتية',
            'message' => $employee->cv
                ? 'ملف السيرة الذاتية لم يعد موجوداً على السيرفر. يرجى إعادة رفعه من صفحة الموظف.'
                : 'لم يتم رفع سيرة ذاتية لهذا الموظف بعد.',
            'back'    => url('/profile/' . $employee->id),
        ], 404);
    }
}
