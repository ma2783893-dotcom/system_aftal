<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    public function dashboard()
    {
        $employees = User::where('role', 'employee')->get();
        return view('admin.dashboard', compact('employees'));
    }
}
