<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

if (file_exists(__DIR__.'/cache/config.php')) {
    @unlink(__DIR__.'/cache/config.php');
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        // تسجيل الخروج لا يحتاج رمز CSRF حتى لا يظهر خطأ 419 عند انتهاء الجلسة
        $middleware->validateCsrfTokens(except: ['logout']);
        $middleware->web(append: [
            \App\Http\Middleware\LanguageSwitcher::class,
            \App\Http\Middleware\EmployeeAccessGuard::class,
            \App\Http\Middleware\NormalizeDigits::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // فشل حفظ ملف مرفوع: ارجع لنفس الصفحة برسالة واضحة
        $exceptions->render(function (\App\Support\FileStoreException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('flash_error', $e->getMessage());
        });

        // بدل صفحة "419 Page Expired": ارجع لصفحة الدخول برسالة واضحة
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'انتهت صلاحية الصفحة. أعد تحميل الصفحة وحاول مرة أخرى',
                ], 419);
            }
            $target = $request->is('login') || !auth()->check() ? '/login' : url()->previous('/');
            $tooBig = empty($request->all()) && (int) $request->server('CONTENT_LENGTH') > 0;
            $msg = $tooBig
                ? 'الملف كبير جداً ولم يصل للسيرفر. جرّب ملفاً أصغر من 10 ميجابايت.'
                : 'انتهت صلاحية الصفحة. أعد المحاولة الآن';
            return redirect($target)
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token', 'cv', 'profile_photo', 'attachment']))
                ->withErrors(['email' => $msg])
                ->with('flash_error', $msg);
        });
    })->create();