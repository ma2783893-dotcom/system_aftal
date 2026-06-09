<?php

namespace App\Http\Controllers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function monthly()
    {
        $employees = User::where('role', 'employee')->with('finance')->get();
        $month     = now()->format('Y-m');

        $pdf = Pdf::loadView('reports.monthly', compact('employees', 'month'))
                  ->setPaper('a4', 'portrait');

        return $pdf->download('monthly-report-' . $month . '.pdf');
    }
}
