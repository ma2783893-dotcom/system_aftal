<?php

namespace App\Support;

use App\Http\Middleware\EmployeeAccessGuard;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Shared checks for check-in / check-out:
 *  - distance from the university gate
 *  - phone-only
 *  - one phone per account, one account per phone (device binding)
 */
class AttendanceGuard
{
    public const UNI_LAT      = 32.028255;
    public const UNI_LNG      = 20.068791;
    public const MAX_DISTANCE = 50; // meters

    public static function distance(float $lat, float $lng): float
    {
        $R  = 6371000;
        $φ1 = deg2rad($lat);
        $φ2 = deg2rad(self::UNI_LAT);
        $Δφ = deg2rad(self::UNI_LAT - $lat);
        $Δλ = deg2rad(self::UNI_LNG - $lng);
        $a  = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Returns null when the device is allowed, or an Arabic error message when it is not.
     * On the employee's first check-in the device is bound to their account.
     */
    public static function checkDevice(Request $request, User $employee): ?string
    {
        if (!EmployeeAccessGuard::isMobile($request->userAgent())) {
            return 'تسجيل الحضور والانصراف متاح من الهاتف المحمول فقط';
        }

        $deviceId = (string) $request->input('device_id', '');
        if (!preg_match('/^[A-Za-z0-9\-]{16,64}$/', $deviceId)) {
            return 'تعذر التعرف على الجهاز. أعد تحميل الصفحة وحاول مجدداً';
        }

        // This phone is already linked to a different employee
        $owner = User::where('device_id', $deviceId)->where('id', '!=', $employee->id)->first();
        if ($owner) {
            return 'هذا الهاتف مسجّل لحساب موظف آخر. لا يمكن استخدام هاتف واحد لأكثر من حساب';
        }

        // First use: bind this phone to the account
        if (!$employee->device_id) {
            $employee->forceFill(['device_id' => $deviceId, 'device_bound_at' => now()])->save();
            return null;
        }

        if (!hash_equals($employee->device_id, $deviceId)) {
            return 'هذا ليس الهاتف المسجّل لحسابك. تواصل مع الإدارة لإعادة تعيين الجهاز';
        }

        return null;
    }
}
