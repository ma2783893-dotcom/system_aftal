@extends('layouts.app')
@section('title', 'بوابة الموظف')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-800">أهلاً بك، {{ $employee->name }}</h2>
        <p class="text-gray-500 mt-2">يمكنك من هنا متابعة بياناتك الوظيفية وساعات عملك.</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-r-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
            <span class="block sm:inline font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-primary">المعلومات الشخصية والوظيفية</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <span class="block text-sm text-gray-500 mb-1">البريد الإلكتروني</span>
                    <span class="font-bold text-gray-800">{{ $employee->email }}</span>
                </div>
                <div>
                    <span class="block text-sm text-gray-500 mb-1">التخصص</span>
                    <span class="font-bold text-gray-800">{{ $employee->specialization ?? 'غير محدد' }}</span>
                </div>
                <div>
                    <span class="block text-sm text-gray-500 mb-1">ساعات العمل اليومية</span>
                    <span class="font-bold text-gray-800">{{ $employee->daily_hours }} ساعة</span>
                </div>
                <div>
                    <span class="block text-sm text-gray-500 mb-1">إجمالي ساعات العمل</span>
                    <span class="font-bold text-primary text-xl">{{ $employee->total_hours }} ساعة</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Finance Summary -->
    @livewire('employee-finance-calculator', ['employeeId' => $employee->id])

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-bold text-primary">السيرة الذاتية (CV)</h3>
        </div>
        <div class="p-6">
            @if($employee->cv_file)
                <div class="mb-6 p-4 bg-blue-50 text-blue-800 border border-blue-100 rounded-lg flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="font-bold">تم رفع السيرة الذاتية مسبقاً</span>
                    </div>
                    <a href="{{ asset('storage/' . $employee->cv_file) }}" target="_blank" class="text-primary hover:underline font-bold text-sm">عرض الملف</a>
                </div>
            @endif

            <form action="{{ route('employee.upload-cv') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">تحديث أو رفع سيرة ذاتية جديدة (PDF فقط)</label>
                    <input type="file" name="cv" accept=".pdf" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-primary file:text-white hover:file:bg-secondary cursor-pointer border border-gray-200 rounded-lg">
                    @error('cv')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="bg-primary hover:bg-secondary text-white font-bold py-2 px-6 rounded-lg transition shadow">رفع الملف</button>
            </form>
        </div>
    </div>
</div>
@endsection
