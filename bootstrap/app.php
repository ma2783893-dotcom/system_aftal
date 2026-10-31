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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
            return redirect($target)
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                ->withErrors(['email' => 'انتهت صلاحية الصفحة. أعد المحاولة الآن']);
        });
    })->create();