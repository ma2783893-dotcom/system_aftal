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
    // Defaults; the admin can change them from "إعدادات الحضور" (stored in app_settings).
    public const UNI_LAT      = 32.028255;
    public const UNI_LNG      = 20.068791;
    public const MAX_DISTANCE = 200; // meters (was 50)
    public const MAX_GPS_TOLERANCE = 50; // extra meters allowed for weak phone GPS

    public static function lat(): float
    {
        return (float) Settings::get('attendance.lat', self::UNI_LAT);
    }

    public static function lng(): float
    {
        return (float) Settings::get('attendance.lng', self::UNI_LNG);
    }

    public static function radius(): int
    {
        return (int) Settings::get('attendance.radius', self::MAX_DISTANCE);
    }

    /** End of the working day, "HH:MM". Used to close days where the employee forgot to check out. */
    public static function workdayEnd(): string
    {
        $v = (string) Settings::get('attendance.workday_end', '15:00');
        return preg_match('/^\d{2}:\d{2}$/', $v) ? $v : '15:00';
    }

    public static function autoCheckoutEnabled(): bool
    {
        return (string) Settings::get('attendance.auto_checkout', '1') === '1';
    }

    /**
     * Automatic check-out: any earlier day that has a check-in but no check-out is closed
     * at the end-of-workday time, and its hours are counted. Runs when pages are opened,
     * so no scheduler is needed. Today's records are left alone (the employee may still check out).
     */
    public static function autoCloseOpenDays(): int
    {
        static $done = false;
        if ($done || !self::autoCheckoutEnabled()) {
            return 0;
        }
        $done = true;

        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('attendances', 'auto_checkout')) {
                return 0;
            }
            $end  = self::workdayEnd();
            $open = \App\Models\Attendance::whereNull('check_out_time')
                ->whereNotNull('check_in_time')
                ->whereDate('date', '<', now()->toDateString())
                ->limit(500)->get();

            foreach ($open as $att) {
                $day     = \Carbon\Carbon::parse($att->date)->toDateString();
                $in      = \Carbon\Carbon::parse($day . ' ' . $att->check_in_time);
                $out     = \Carbon\Carbon::parse($day . ' ' . $end . ':00');
                if ($out->lt($in)) {
                    $out = $in->copy();      // checked in after the workday ended: 0 hours
                }
                $att->forceFill([
                    'check_out_time' => $out->format('H:i:s'),
                    'hours_worked'   => round($in->diffInMinutes($out) / 60, 2),
                    'auto_checkout'  => true,
                    'check_out_verified' => false,
                ])->save();
            }
            return $open->count();
        } catch (\Throwable $e) {
            \Log::warning('autoCloseOpenDays: ' . $e->getMessage());
            return 0;
        }
    }

    /** Inside the range, allowing a little extra when the phone reports a weak GPS fix. */
    public static function within(float $distance, $accuracy = null): bool
    {
        $tolerance = is_numeric($accuracy) ? min(max((float) $accuracy, 0), self::MAX_GPS_TOLERANCE) : 0;
        return $distance <= self::radius() + $tolerance;
    }

    public static function bindingEnabled(): bool
    {
        return filter_var(env('ATTENDANCE_BIND_DEVICE', false), FILTER_VALIDATE_BOOLEAN);
    }

    public static function distance(float $lat, float $lng): float
    {
        $R  = 6371000;
        $φ1 = deg2rad($lat);
        $φ2 = deg2rad(self::lat());
        $Δφ = deg2rad(self::lat() - $lat);
        $Δλ = deg2rad(self::lng() - $lng);
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

        // ربط الحساب بهاتف واحد متوقف افتراضياً: أي هاتف يقدر يسجّل.
        // لتفعيله من جديد أضف في Railway المتغير ATTENDANCE_BIND_DEVICE=true
        if (!self::bindingEnabled()) {
            return null;
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
