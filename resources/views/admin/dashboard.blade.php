@extends('layouts.app')
@section('title', __('Admin Dashboard'))

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Stats Cards --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
        <div style="background:#0a2540; color:white; padding:1.5rem; border-radius:1rem; text-align:center;">
            <div style="font-size:2rem; font-weight:bold;">{{ $totalEmployees }}</div>
            <div style="color:#94a3b8; margin-top:0.5rem;">👥 {{ __('Total Employees') }}</div>
        </div>
        <div style="background:#1d4ed8; color:white; padding:1.5rem; border-radius:1rem; text-align:center;">
            <div style="font-size:2rem; font-weight:bold;">{{ $totalHours }}</div>
            <div style="color:#bfdbfe; margin-top:0.5rem;">⏱️ {{ __('Total Hours') }}</div>
        </div>
        <div style="background:#15803d; color:white; padding:1.5rem; border-radius:1rem; text-align:center;">
            <div style="font-size:2rem; font-weight:bold;">{{ number_format((float)$totalSalaries, 0) }}</div>
            <div style="color:#bbf7d0; margin-top:0.5rem;">💰 {{ __('Total Salaries') }}</div>
        </div>
        <div style="background:#b45309; color:white; padding:1.5rem; border-radius:1rem; text-align:center;">
            <div style="font-size:2rem; font-weight:bold;">{{ $totalDepartments }}</div>
            <div style="color:#fde68a; margin-top:0.5rem;">🏛️ {{ __('Departments') }}</div>
        </div>
    </div>

    {{-- Header Row --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <h1 class="text-3xl font-bold text-gray-800">{{ __('Employee List') }}</h1>
        <div style="display:flex; gap:0.75rem; align-items:center; flex-wrap:wrap;">
            <a href="/attendance/calendar"
               style="background:#0891b2; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem; font-weight:600;">
                📅 {{ __('Attendance Calendar') }}
            </a>
            <a href="/reports/monthly"
               style="background:#7c3aed; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem; font-weight:600;">
                📊 {{ __('Monthly Report') }}
            </a>
            <a href="/activity-log"
               style="background:#374151; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:0.875rem; font-weight:600;">
                📋 سجل النشاط
            </a>
            <button onclick="document.getElementById('addSupervisorModal').classList.remove('hidden')"
                style="background:#7c3aed; color:white; padding:10px 20px; border-radius:8px; border:none; cursor:pointer; font-size:1rem; font-weight:bold;">
                + إضافة مشرف
            </button>
            <button onclick="document.getElementById('addModal').style.display='flex'"
                style="background:#16a34a; color:white; padding:10px 20px; border-radius:8px; border:none; cursor:pointer; font-size:1rem; font-weight:bold;">
                + {{ __('Add New Employee') }}
            </button>
        </div>
    </div>

    {{-- Smart Search --}}
    <input type="text" id="searchBox" onkeyup="applyFilters()"
        placeholder="🔍 {{ __('Search by name, email or specialization...') }}"
        style="width:100%; padding:12px 16px; border:2px solid #e2e8f0; border-radius:10px;
               font-size:1rem; margin-bottom:1rem; font-family:'Tajawal',sans-serif;
               outline:none; box-sizing:border-box; transition:border-color 0.2s;"
        onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">

    {{-- Advanced Filter --}}
    <div style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <select onchange="applyFilters()" id="filterSpec"
            style="padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px;
                   font-family:'Tajawal',sans-serif; background:white; min-width:180px;">
            <option value="">{{ __('All Specializations') }}</option>
            @foreach($specializations as $spec)
                <option value="{{ $spec }}">{{ $spec }}</option>
            @endforeach
        </select>
    </div>

    {{-- Add Employee Modal --}}
    <div id="addModal" style="display:{{ $errors->any() ? 'flex' : 'none' }}; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:999; align-items:center; justify-content:center;">
        <div style="background:white; padding:2rem; border-radius:1rem; width:90%; max-width:620px; position:relative; max-height:90vh; overflow-y:auto;">
            <button onclick="document.getElementById('addModal').style.display='none'"
                style="position:absolute; top:10px; left:10px; background:none; border:none; font-size:1.5rem; cursor:pointer; line-height:1; color:#64748b;">✕</button>
            <h2 style="margin-bottom:1.5rem; color:#0a2540; font-size:1.25rem; font-weight:700;">{{ __('Add New Employee') }}</h2>
            @if($errors->any())
            <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:12px; margin-bottom:1rem;">
                @foreach($errors->all() as $err)
                    <p style="color:#dc2626; font-size:0.875rem; margin:2px 0;">⚠️ {{ $err }}</p>
                @endforeach
            </div>
            @endif
            @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px; margin-bottom:1rem;">
                <p style="color:#15803d; font-size:0.875rem; margin:0;">✅ {{ session('success') }}</p>
            </div>
            @endif
            <form action="/add-employee" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <input type="text" name="name" placeholder="{{ __('Name') }}"
                        class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    <input type="email" name="email" placeholder="{{ __('Email') }}"
                        class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    <input type="text" name="specialization" list="spec-list"
                        placeholder="{{ __('Specialization') }}"
                        class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                    <datalist id="spec-list">
                        @foreach($specializations as $spec)
                            <option value="{{ $spec }}">
                        @endforeach
                        <option value="محاضر">
                        <option value="أستاذ مساعد">
                        <option value="أستاذ مشارك">
                        <option value="أستاذ">
                        <option value="مهندس">
                        <option value="محاسب">
                        <option value="إداري">
                    </datalist>
                    <input type="password" name="password" placeholder="{{ __('Password') }}"
                        class="border border-gray-300 p-2 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                        📷 {{ __('Profile Photo') }}
                    </label>
                    <input type="file" name="profile_photo" accept="image/*"
                        class="text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                </div>
                <div class="flex items-center justify-between gap-4" style="flex-wrap:wrap;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; color:#374151; margin-bottom:0.4rem;">
                            📄 {{ __('Resume (PDF)') }}
                        </label>
                        <input type="file" name="cv" accept=".pdf"
                            class="text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2 rounded transition">
                        {{ __('Add New Employee') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Supervisor Modal --}}
    <div id="addSupervisorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md" style="max-height:90vh; overflow-y:auto;">
            <h2 style="font-size:1.25rem; font-weight:700; color:#0a2540; margin-bottom:1.25rem;">إضافة مشرف جديد</h2>
            <form method="POST" action="/add-supervisor">
                @csrf
                <input type="text" name="name" placeholder="الاسم" required
                       class="w-full border border-gray-300 p-2 rounded mb-3 font-inherit">
                <input type="email" name="email" placeholder="البريد الإلكتروني" required
                       class="w-full border border-gray-300 p-2 rounded mb-3 font-inherit">
                <input type="password" name="password" placeholder="كلمة المرور (6 أحرف على الأقل)" required
                       class="w-full border border-gray-300 p-2 rounded mb-4 font-inherit">

                <p style="font-weight:700; margin-bottom:0.6rem; color:#374151;">الصلاحيات:</p>
                @foreach([
                    'add_employee'    => 'إضافة موظفين',
                    'edit_employee'   => 'تعديل بيانات الموظفين',
                    'delete_employee' => 'حذف الموظفين',
                    'view_finance'    => 'عرض البيانات المالية',
                    'edit_finance'    => 'تعديل البيانات المالية',
                    'view_reports'    => 'عرض التقارير',
                ] as $val => $label)
                <label class="flex items-center gap-2 mb-2 cursor-pointer">
                    <input type="checkbox" name="permissions[]" value="{{ $val }}">
                    <span>{{ $label }}</span>
                </label>
                @endforeach

                <div class="flex gap-2 mt-5">
                    <button type="submit"
                            style="background:#7c3aed; color:white; padding:10px 0; border-radius:8px; border:none; cursor:pointer; font-weight:700; flex:1; font-family:inherit;">
                        حفظ المشرف
                    </button>
                    <button type="button"
                            onclick="document.getElementById('addSupervisorModal').classList.add('hidden')"
                            style="background:#e5e7eb; color:#374151; padding:10px 0; border-radius:8px; border:none; cursor:pointer; font-weight:600; flex:1; font-family:inherit;">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Employee Table --}}
    <div class="bg-white p-4 rounded shadow overflow-x-auto">
        <table class="w-full text-start border-collapse">
            <thead>
                <tr class="bg-gray-200 text-gray-700">
                    <th class="p-3 text-start">{{ __('Name') }}</th>
                    <th class="p-3 text-start">{{ __('Email') }}</th>
                    <th class="p-3 text-start">{{ __('Specialization') }}</th>
                    <th class="p-3 text-start">{{ __('Resume') }}</th>
                    <th class="p-3 text-start">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr class="border-t">
                        <td class="p-3">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <img src="{{ $emp->profile_photo ? asset('uploads/photos/' . $emp->profile_photo) : '/assets/default-avatar.svg' }}"
                                     style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #e5e7eb;"
                                     onerror="this.src='/assets/default-avatar.svg'">
                                <span>{{ $emp->name }}</span>
                            </div>
                        </td>
                        <td class="p-3">{{ $emp->email }}</td>
                        <td class="p-3">{{ $emp->specialization }}</td>
                        <td class="p-3">
                            @if($emp->cv)
                                <div class="flex gap-2">
                                    <a href="/profile/{{ $emp->id }}"
                                        class="text-blue-500 hover:text-blue-700 font-medium underline">{{ __('View Profile') }}</a>
                                    <span class="text-gray-300">|</span>
                                    <a href="{{ route('resume.download', $emp->id) }}"
                                        class="text-green-500 hover:text-green-700 font-medium underline">{{ __('Download') }}</a>
                                </div>
                            @else
                                <span class="text-gray-500">{{ __('Not Available') }}</span>
                            @endif
                        </td>
                        <td class="p-3 flex gap-2">
                            <a href="/edit-employee/{{ $emp->id }}"
                                class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600 text-sm">{{ __('Edit') }}</a>
                            <a href="/delete-employee/{{ $emp->id }}"
                                class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700 text-sm"
                                onclick="return confirm('Are you sure?')">{{ __('Delete') }}</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function applyFilters() {
    const input = document.getElementById('searchBox').value.toLowerCase();
    const spec  = document.getElementById('filterSpec').value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(row => {
        const text    = row.textContent.toLowerCase();
        const rowSpec = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
        row.style.display = ((!input || text.includes(input)) && (!spec || rowSpec.includes(spec))) ? '' : 'none';
    });
}
</script>
@endsection
