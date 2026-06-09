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

    <footer style="
        background: #0a2540;
        color: white;
        padding: 2rem 1rem;
        margin-top: 3rem;
        font-family: 'Tajawal', sans-serif;
        direction: rtl;
        position: relative;
        z-index: 1;
    ">
        <div style="max-width:1200px; margin:0 auto;">

            <!-- Top Row -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; border-bottom:1px solid rgba(255,255,255,0.15); padding-bottom:1.5rem; margin-bottom:1.5rem;">

                <!-- Logo & Name -->
                <div style="display:flex; align-items:center; gap:12px;">
                    <img src="/assets/logo-dark.jpg"
                         style="width:50px; height:50px; border-radius:50%; object-fit:contain;">
                    <div>
                        <div style="font-weight:bold; font-size:1.1rem;">جامعة الأفضل الدولية</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">Al Afdal International University</div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div style="display:flex; gap:1.5rem; font-size:0.9rem; color:#94a3b8;">
                    <span>📧 ma2783893@gmail.com</span>
                    <span>📞 +218 919004828</span>
                    <span>📍 ليبيا</span>
                </div>

                <!-- System Info -->
                <div style="text-align:center; font-size:0.8rem; color:#64748b;">
                    <div>نظام إدارة أعضاء هيئة التدريس</div>
                    <div style="color:#3b82f6;">الإصدار 1.0.0</div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">

                <!-- Copyright -->
                <div style="font-size:0.85rem; color:#94a3b8;">
                    © 2026 جامعة الأفضل الدولية — جميع الحقوق محفوظة
                </div>

                <!-- Badges -->
                <div style="display:flex; gap:0.75rem;">
                    <span style="background:rgba(59,130,246,0.15); color:#60a5fa; padding:4px 10px; border-radius:20px; font-size:0.75rem;">
                        🔒 نظام آمن
                    </span>
                    <span style="background:rgba(16,185,129,0.15); color:#34d399; padding:4px 10px; border-radius:20px; font-size:0.75rem;">
                        ✓ مرخّص رسمياً
                    </span>
                    <span style="background:rgba(251,191,36,0.15); color:#fbbf24; padding:4px 10px; border-radius:20px; font-size:0.75rem;">
                        ⚡ Laravel 12
                    </span>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>