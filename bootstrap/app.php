<?php
// أضف هذا السطر في بداية الملف لفرض التعريف
putenv("DB_CONNECTION=mysql");
$_ENV['DB_CONNECTION'] = 'mysql';

return Application::configure(basePath: dirname(__DIR__))
// ...

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// 🔥 خط الدفاع الأول: كسر كاش ريلواي المجمّد تلقائياً عند التشغيل بقوة الكود
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
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\LanguageSwitcher::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();