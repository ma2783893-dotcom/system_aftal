<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جامعة الأفضل الدولية - @yield('title')</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0a2540">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f3f4f6;
        }
        /* Dark Mode */
        .dark-mode {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
        }
        .dark-mode::before {
            background-color: #0f172a !important;
        }
        .dark-mode .bg-white {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }
        .dark-mode table {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }
        .dark-mode th {
            background-color: #0f172a !important;
            color: #94a3b8 !important;
        }
        .dark-mode td {
            color: #e2e8f0 !important;
            border-color: #334155 !important;
        }
        .dark-mode .text-gray-800 { color: #e2e8f0 !important; }
        .dark-mode .text-gray-700 { color: #cbd5e1 !important; }
        .dark-mode .text-gray-500 { color: #94a3b8 !important; }
        .dark-mode input, .dark-mode select {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
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


    <nav class="bg-primary text-white shadow-lg shadow-gray-200" style="position:relative; z-index:10;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="/" class="font-bold text-xl tracking-wide">جامعة الأفضل</a>
                    </div>
                </div>
                <div class="flex gap-4 items-center">

                    {{-- Dark Mode Toggle Switch (auth only) --}}
                    @auth
                    <div onclick="toggleDark()" id="darkToggle"
                        style="width:52px; height:28px; background:rgba(255,255,255,0.2);
                               border-radius:14px; cursor:pointer; position:relative;
                               transition:background 0.3s ease; border:1px solid rgba(255,255,255,0.3);
                               flex-shrink:0;"
                        title="تغيير المظهر">
                        <div id="darkCircle"
                            style="position:absolute; top:3px; left:3px; width:20px; height:20px;
                                   background:white; border-radius:50%; transition:all 0.3s ease;
                                   display:flex; align-items:center; justify-content:center;
                                   font-size:0.7rem;">🌙</div>
                    </div>
                    @endauth

                    {{-- Notification Bell (Admin only) --}}
                    @auth
                    @if(auth()->user()->isAdmin())
                    <div style="position:relative; cursor:pointer;" onclick="toggleNotifications()">
                        <span style="font-size:1.4rem; line-height:1;">🔔</span>
                        @if(isset($unreadCount) && $unreadCount > 0)
                        <span style="position:absolute; top:-6px; right:-6px; background:red; color:white;
                                     border-radius:50%; width:18px; height:18px; font-size:0.65rem;
                                     display:flex; align-items:center; justify-content:center; font-weight:bold;">
                            {{ $unreadCount }}
                        </span>
                        @endif
                    </div>

                    <div id="notifDropdown" style="display:none; position:absolute; top:64px;
                         right:20px; background:white; color:#333; border-radius:12px;
                         width:320px; box-shadow:0 10px 30px rgba(0,0,0,0.2); z-index:999; padding:1rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                            <h4 style="color:#0a2540; font-weight:bold; margin:0;">🔔 الإشعارات</h4>
                            <a href="/notifications/mark-read" style="font-size:0.75rem; color:#3b82f6; text-decoration:none;">
                                تعيين الكل كمقروء
                            </a>
                        </div>
                        @if(isset($notifications) && $notifications->count())
                            @foreach($notifications as $notif)
                            <div style="padding:0.6rem; border-bottom:1px solid #f1f5f9; font-size:0.875rem;
                                        background:{{ $notif->is_read ? 'transparent' : '#eff6ff' }}; border-radius:4px;">
                                {{ $notif->message }}
                                <div style="color:#94a3b8; font-size:0.72rem; margin-top:3px;">{{ $notif->created_at->diffForHumans() }}</div>
                            </div>
                            @endforeach
                        @else
                            <p style="color:#94a3b8; font-size:0.875rem; text-align:center; padding:1rem 0;">لا توجد إشعارات</p>
                        @endif
                    </div>
                    @endif
                    @endauth

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
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; border-bottom:1px solid rgba(255,255,255,0.15); padding-bottom:1.5rem; margin-bottom:1.5rem;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <img src="/assets/logo-dark.jpg" style="width:50px; height:50px; border-radius:50%; object-fit:contain;">
                    <div>
                        <div style="font-weight:bold; font-size:1.1rem;">جامعة الأفضل الدولية</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">Al Afdal International University</div>
                    </div>
                </div>
                <div style="display:flex; gap:1.5rem; font-size:0.9rem; color:#94a3b8;">
                    <span>📧 ma2783893@gmail.com</span>
                    <span>📞 +218 919004828</span>
                    <span>📍 ليبيا</span>
                </div>
                <div style="text-align:center; font-size:0.8rem; color:#64748b;">
                    <div>نظام إدارة أعضاء هيئة التدريس</div>
                    <div style="color:#3b82f6;">الإصدار 1.0.0</div>
                </div>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
                <div style="font-size:0.85rem; color:#94a3b8;">
                    © 2026 جامعة الأفضل الدولية — جميع الحقوق محفوظة
                </div>
                <div style="display:flex; gap:0.75rem;">
                    <span style="background:rgba(59,130,246,0.15); color:#60a5fa; padding:4px 10px; border-radius:20px; font-size:0.75rem;">🔒 نظام آمن</span>
                    <span style="background:rgba(16,185,129,0.15); color:#34d399; padding:4px 10px; border-radius:20px; font-size:0.75rem;">✓ مرخّص رسمياً</span>
                    <span style="background:rgba(251,191,36,0.15); color:#fbbf24; padding:4px 10px; border-radius:20px; font-size:0.75rem;">⚡ Laravel 12</span>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts

    <script>
    // Dark Mode Toggle Switch
    function toggleDark() {
        document.body.classList.toggle('dark-mode');
        const isDark = document.body.classList.contains('dark-mode');
        const circle = document.getElementById('darkCircle');
        const toggle = document.getElementById('darkToggle');
        if (isDark) {
            circle.style.left = '27px';
            circle.textContent = '☀️';
            toggle.style.background = '#1e3a8a';
        } else {
            circle.style.left = '3px';
            circle.textContent = '🌙';
            toggle.style.background = 'rgba(255,255,255,0.2)';
        }
        localStorage.setItem('darkMode', isDark);
    }
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
        const circle = document.getElementById('darkCircle');
        const toggle = document.getElementById('darkToggle');
        if (circle) { circle.style.left = '27px'; circle.textContent = '☀️'; }
        if (toggle) toggle.style.background = '#1e3a8a';
    }

    // Close notification dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown && !e.target.closest('[onclick="toggleNotifications()"]') && !e.target.closest('#notifDropdown')) {
            dropdown.style.display = 'none';
        }
    });

    function toggleNotifications() {
        const d = document.getElementById('notifDropdown');
        if (d) d.style.display = d.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</body>
</html>
