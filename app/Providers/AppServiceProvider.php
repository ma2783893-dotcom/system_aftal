<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\AppNotification;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            if (auth()->check() && auth()->user()->isAdmin()) {
                try {
                    $notifications = AppNotification::latest()->take(10)->get();
                    $unreadCount   = AppNotification::where('is_read', false)->count();
                } catch (\Exception $e) {
                    $notifications = collect();
                    $unreadCount   = 0;
                }
            } else {
                $notifications = collect();
                $unreadCount   = 0;
            }
            $view->with(compact('notifications', 'unreadCount'));
        });
    }
}
