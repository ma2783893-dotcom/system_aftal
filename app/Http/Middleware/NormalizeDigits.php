<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phones with an Arabic keyboard type Arabic-Indic digits (٠١٢٣٤٥٦٧٨٩) or Persian ones (۰۱۲…).
 * Passwords like 7738181 then never match, and the user sees "البيانات المدخلة غير صحيحة".
 * Convert those digits to normal ones in login/password fields before anything else runs.
 */
class NormalizeDigits
{
    private const FIELDS = ['email', 'password', 'password_confirmation', 'current_password'];

    public static function convert(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $changes = [];
        foreach (self::FIELDS as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $clean = self::convert($value);
                if ($field === 'email') {
                    $clean = trim($clean);
                }
                if ($clean !== $value) {
                    $request->attributes->set('raw_' . $field, $value);
                    $changes[$field] = $clean;
                }
            }
        }
        if ($changes) {
            $request->merge($changes);
        }
        return $next($request);
    }
}
