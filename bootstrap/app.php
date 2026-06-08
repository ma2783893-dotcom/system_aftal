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
    ->withMiddleware(function (Middleware ): void {
        ->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
        ->web(append: [
            \App\Http\Middleware\LanguageSwitcher::class,
        ]);
    })
    ->withExceptions(function (Exceptions ): void {
        //
    })->create();
