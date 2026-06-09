<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جامعة الأفضل الدولية - @yield('title')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0a2540',
                        secondary: '#1e3a8a',
                    }
                }
            }
        }
    </script>
    @livewireStyles
</head>
<body class="text-gray-800 antialiased">

    <!-- Watermark -->
    <img src="/assets/logo.png"
         style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
                width:420px; opacity:0.15; z-index:0; pointer-events:none;">

    <nav class="bg-primary text-white shadow-lg shadow-gray-200" style="position:relative; z-index:10;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="/" class="font-bold text-xl tracking-wide">جامعة الأفضل</a>
                    </div>
                </div>
                <div class="flex gap-4 items-center">
                    <div class="flex items-center rounded-md shrink-0 border-r border-white/20 pr-4 mr-4" dir="ltr">
                        <a href="{{ route('lang.switch', 'ar') }}" class="px-3 py-1 text-sm {{ app()->getLocale() == 'ar' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }} rounded-l-md transition">Ar</a>
                        <a href="{{ route('lang.switch', 'en') }}" class="px-3 py-1 text-sm {{ app()->getLocale() == 'en' ? 'bg-blue-600 text-white font-bold' : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }} rounded-r-md transition">En</a>
                    </div>
                    @auth
                        <span class="text-sm font-medium">{{ Auth::user()->name }}</span>
                    @endauth
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-sm font-bold transition shadow-sm">{{ __('Logout') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main class="py-8" style="position:relative; z-index:1;">
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>