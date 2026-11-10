<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Small key/value settings store that the admin can change from the system
 * (e.g. the attendance radius) without touching code or Railway variables.
 */
class Settings
{
    private static ?array $cache = null;

    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                if (Schema::hasTable('app_settings')) {
                    self::$cache = DB::table('app_settings')->pluck('value', 'key')->all();
                }
            } catch (\Throwable $e) {
                // Database not reachable or not migrated yet: fall back to defaults.
            }
        }
        return array_key_exists($key, self::$cache) && self::$cache[$key] !== null ? self::$cache[$key] : $default;
    }

    public static function set(string $key, $value): void
    {
        if (!Schema::hasTable('app_settings')) {
            Artisan::call('migrate', ['--force' => true]);
        }
        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
        );
        self::$cache = null;
    }
}
