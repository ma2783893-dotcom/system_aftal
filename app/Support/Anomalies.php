<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Looks for check-ins that do not look like a real person on a real phone.
 * Nothing here punishes anyone: it only produces a list for the admin to review.
 */
class Anomalies
{
    public static function scan(int $days = 30): array
    {
        $since = now()->subDays($days)->toDateString();
        $hasAcc = Schema::hasColumn('attendances', 'accuracy');

        $rows = Attendance::query()
            ->whereNotNull('check_in_time')
            ->whereDate('date', '>=', $since)
            ->where(fn ($q) => $q->where('manual', false)->orWhereNull('manual'))
            ->orderBy('date')
            ->get();

        $names = User::pluck('name', 'id');
        $name  = fn ($id) => $names[$id] ?? ('#' . $id);
        $d     = fn ($a) => \Carbon\Carbon::parse($a->date)->format('d/m');

        $findings = [];

        // 1) one phone used by two or more accounts
        foreach ($rows->whereNotNull('device_id')->where('device_id', '!=', '')->groupBy('device_id') as $device => $list) {
            $people = $list->pluck('employee_id')->unique();
            if ($people->count() > 1) {
                $findings[] = [
                    'level'  => 'high',
                    'title'  => 'هاتف واحد سجّل لأكثر من موظف',
                    'people' => $people->map($name)->values()->all(),
                    'detail' => 'نفس الهاتف استُخدم في ' . $list->count() . ' تسجيلات: '
                        . $list->map(fn ($a) => $name($a->employee_id) . ' (' . $d($a) . ' ' . substr($a->check_in_time, 0, 5) . ')')->implode('، '),
                    'why'    => 'غالباً موظف يسجّل الحضور بدل زميله من هاتفه.',
                ];
            }
        }

        // 2) exactly the same coordinates on several different days (real GPS always moves a little)
        foreach ($rows->whereNotNull('latitude')->groupBy('employee_id') as $emp => $list) {
            foreach ($list->groupBy(fn ($a) => round((float) $a->latitude, 6) . ',' . round((float) $a->longitude, 6)) as $point => $same) {
                $dates = $same->map($d)->unique();
                if ($dates->count() >= 3) {
                    $findings[] = [
                        'level'  => 'high',
                        'title'  => 'نفس الإحداثيات بالضبط في أيام مختلفة',
                        'people' => [$name($emp)],
                        'detail' => $dates->count() . ' أيام بنفس النقطة تماماً (' . $point . '): ' . $dates->implode('، '),
                        'why'    => 'الـGPS الحقيقي يتغيّر بأمتار كل مرة. التطابق التام علامة على برنامج تزييف الموقع.',
                    ];
                }
            }
        }

        // 3) two different people at exactly the same point within a few minutes
        foreach ($rows->whereNotNull('latitude')->groupBy(fn ($a) => $a->date . '|' . round((float) $a->latitude, 6) . ',' . round((float) $a->longitude, 6)) as $key => $same) {
            $people = $same->pluck('employee_id')->unique();
            if ($people->count() > 1) {
                $times = $same->map(fn ($a) => strtotime($a->check_in_time))->sort()->values();
                if ($times->last() - $times->first() <= 300) {
                    $findings[] = [
                        'level'  => 'medium',
                        'title'  => 'موظفان في نفس النقطة بالضبط وفي نفس الدقائق',
                        'people' => $people->map($name)->values()->all(),
                        'detail' => 'يوم ' . $d($same->first()) . ': ' . $same->map(fn ($a) => $name($a->employee_id) . ' ' . substr($a->check_in_time, 0, 5))->implode('، '),
                        'why'    => 'قد يكونان معاً فعلاً، وقد يكون نفس الشخص سجّل للاثنين.',
                    ];
                }
            }
        }

        // 4) GPS accuracy that never changes (fake-location apps often report a fixed number)
        if ($hasAcc) {
            foreach ($rows->whereNotNull('accuracy')->groupBy('employee_id') as $emp => $list) {
                if ($list->count() < 3) continue;
                $values = $list->pluck('accuracy')->map(fn ($v) => (float) $v);
                if ($values->unique()->count() === 1) {
                    $findings[] = [
                        'level'  => 'medium',
                        'title'  => 'دقة GPS ثابتة لا تتغيّر',
                        'people' => [$name($emp)],
                        'detail' => $list->count() . ' تسجيلات كلها بدقة ±' . rtrim(rtrim(number_format($values->first(), 2), '0'), '.') . ' متر',
                        'why'    => 'الهاتف الحقيقي يعطي دقة مختلفة كل مرة. الرقم الثابت علامة على برنامج تزييف.',
                    ];
                }
            }
        }

        // 5) many check-ins outside the university area
        foreach ($rows->groupBy('employee_id') as $emp => $list) {
            $out = $list->where('location_verified', false);
            if ($out->count() >= 3) {
                $findings[] = [
                    'level'  => 'low',
                    'title'  => 'تسجيلات متكررة خارج نطاق الجامعة',
                    'people' => [$name($emp)],
                    'detail' => $out->count() . ' من ' . $list->count() . ' تسجيلات خارج النطاق، أبعد مسافة ' . round((float) $out->max('distance_meters')) . ' متر',
                    'why'    => 'قد يكون ضعف إشارة GPS، أو يسجّل من خارج الجامعة.',
                ];
            }
        }

        $order = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($findings, fn ($a, $b) => $order[$a['level']] <=> $order[$b['level']]);

        return ['findings' => $findings, 'checked' => $rows->count(), 'days' => $days];
    }
}
