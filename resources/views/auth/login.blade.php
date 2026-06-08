@extends('layouts.app')
@section('title', 'تسجيل الدخول')

@section('content')
    <!-- Watermark -->
    <div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:0;pointer-events:none;display:flex;align-items:center;justify-content:center;overflow:hidden;">
        <img src="{{ asset('assets/logo.png') }}" alt="" style="width:50%;max-width:440px;opacity:0.09;user-select:none;-webkit-user-select:none;">
    </div>

    <div class="min-h-[80vh] flex items-center justify-center" style="position:relative;z-index:1;">
        <div
            class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-10 transform transition-all hover:-translate-y-1">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-black text-primary mb-3">جامعة الأفضل الدولية</h1>
                <p class="text-sm text-gray-500 font-medium">نظام إدارة أعضاء هيئة التدريس والموظفين</p>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border-r-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                    <span class="block sm:inline font-medium text-sm">{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label for="email" class="block text-sm font-bold text-gray-700 mb-2">البريد الإلكتروني</label>
                    <input type="email" id="email" name="email"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200"
                        required placeholder="البريد الإلكتروني" value="{{ old('email') }}">
                </div>

                <div class="mb-8">
                    <label for="password" class="block text-sm font-bold text-gray-700 mb-2">كلمة المرور</label>
                    <input type="password" id="password" name="password"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all duration-200"
                        required placeholder="••••••••">
                </div>

                <button type="submit"
                    class="w-full bg-primary hover:bg-secondary text-white font-bold py-3.5 px-4 rounded-xl transition duration-300 shadow hover:shadow-lg hover:shadow-primary/30 flex justify-center items-center gap-2">
                    <span>تسجيل الدخول</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm-2.707-8.707l3-3a1 1 0 011.414 1.414L9.414 10l2.293 2.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
@endsection
