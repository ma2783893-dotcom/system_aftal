<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/** Builds the attendance report (one employee or everyone, for a date range). */
class AttendanceReport
{
    public Carbon $from;
    public Carbon $to;
    public ?User $employee = null;
    /** @var \Illuminate\Support\Collection */
    public $rows;
    /** @var \Illuminate\Support\Collection */
    public $summary;
    public float $totalHours = 0;

    public static function fromRequest(Request $request): self
    {
        $r = new self();
        try {
            $r->from = Carbon::parse($request->input('from') ?: now()->startOfMonth())->startOfDay();
        } catch (\Throwable $e) {
            $r->from = now()->startOfMonth();
        }
        try {
            $r->to = Carbon::parse($request->input('to') ?: now())->endOfDay();
        } catch (\Throwable $e) {
            $r->to = now()->endOfDay();
        }
        if ($r->to->lt($r->from)) {
            [$r->from, $r->to] = [$r->to->copy()->startOfDay(), $r->from->copy()->endOfDay()];
        }
        if ($request->filled('employee_id')) {
            $r->employee = User::where('role', 'employee')->find($request->input('employee_id'));
        }
        $r->build();
        return $r;
    }

    private function build(): void
    {
        $q = Attendance::with('employee')
            ->whereBetween('date', [$this->from->toDateString(), $this->to->toDateString()]);
        if ($this->employee) {
            $q->where('employee_id', $this->employee->id);
        }
        $records = $q->get()->filter(fn ($a) => $a->employee)->sortBy([
            fn ($a, $b) => strcmp($a->employee->name, $b->employee->name),
            fn ($a, $b) => strcmp((string) $a->date, (string) $b->date),
        ])->values();

        $this->rows = $records->map(function ($a) {
            $hours = $a->check_out_time ? (float) $a->hours_worked : 0.0;
            $present = (bool) $a->check_in_time;
            return [
                'employee_id'    => $a->employee_id,
                'name'           => $a->employee->name,
                'specialization' => $a->employee->specialization,
                'date'           => Carbon::parse($a->date),
                'in'             => $a->check_in_time ? substr($a->check_in_time, 0, 5) : null,
                'out'            => $a->check_out_time ? substr($a->check_out_time, 0, 5) : null,
                'hours'          => round($hours, 2),
                'in_ok'          => (bool) $a->location_verified,
                'out_ok'         => $a->check_out_time && !$a->auto_checkout ? (bool) $a->check_out_verified : null,
                'auto'           => (bool) ($a->auto_checkout ?? false),
                'present'        => $present,
                'note'           => $a->note,
                'manual'         => (bool) ($a->manual ?? false),
            ];
        });

        // Summary per employee; everyone listed when "all employees" is chosen, including those with no records.
        $people = $this->employee
            ? collect([$this->employee])
            : User::where('role', 'employee')->orderBy('name')->get();

        $this->summary = $people->map(function ($u) {
            $all  = $this->rows->where('employee_id', $u->id);
            $mine = $all->where('present', true);
            return [
                'name'           => $u->name,
                'specialization' => $u->specialization,
                'days'           => $mine->count(),
                'hours'          => round($mine->sum('hours'), 2),
                'no_out'         => $mine->whereNull('out')->count(),
                'out_of_range'   => $mine->filter(fn ($r) => !$r['in_ok'] || $r['out_ok'] === false)->count(),
                'auto'           => $mine->where('auto', true)->count(),
                'notes'          => $all->filter(fn ($r) => $r['note'])->count(),
            ];
        });

        $this->totalHours = round($this->rows->sum('hours'), 2);
    }

    public static function hm(float $hours): string
    {
        $m = (int) round($hours * 60);
        return intdiv($m, 60) . ':' . str_pad((string) ($m % 60), 2, '0', STR_PAD_LEFT);
    }

    public function title(): string
    {
        return 'تقرير الحضور' . ($this->employee ? ' - ' . $this->employee->name : ' - جميع الموظفين');
    }

    public function period(): string
    {
        return $this->from->format('d/m/Y') . ' إلى ' . $this->to->format('d/m/Y');
    }

    public function toXlsx(string $path): void
    {
        $days = ['Sunday' => 'الأحد', 'Monday' => 'الاثنين', 'Tuesday' => 'الثلاثاء', 'Wednesday' => 'الأربعاء',
                 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'];

        $summary = [['الاسم', 'الصفة', 'أيام الحضور', 'إجمالي الساعات', 'الساعات (س:د)', 'أيام بدون انصراف', 'انصراف تلقائي', 'تسجيلات خارج النطاق']];
        foreach ($this->summary as $s) {
            $summary[] = [$s['name'], $s['specialization'], (int) $s['days'], (float) $s['hours'], self::hm($s['hours']), (int) $s['no_out'], (int) $s['auto'], (int) $s['out_of_range']];
        }
        $summary[] = ['الإجمالي', '', (int) $this->summary->sum('days'), (float) $this->totalHours, self::hm($this->totalHours), (int) $this->summary->sum('no_out'), (int) $this->summary->sum('auto'), (int) $this->summary->sum('out_of_range')];
        $summary[] = [];
        $summary[] = ['الفترة: ' . $this->period()];

        $detail = [['الاسم', 'الصفة', 'التاريخ', 'اليوم', 'وقت الحضور', 'وقت الانصراف', 'الساعات', 'الساعات (س:د)', 'موقع الحضور', 'موقع الانصراف', 'ملاحظة']];
        foreach ($this->rows as $r) {
            $detail[] = [
                $r['name'], $r['specialization'], $r['date']->format('Y-m-d'), $days[$r['date']->format('l')] ?? '',
                $r['present'] ? ($r['in'] ?? '') : 'غياب',
                $r['present'] ? ($r['out'] ? ($r['out'] . ($r['auto'] ? ' (تلقائي)' : '') . ($r['manual'] ? ' (الإدارة)' : '')) : 'لم يسجّل انصراف') : '',
                (float) $r['hours'], self::hm($r['hours']),
                $r['present'] ? ($r['in_ok'] ? 'داخل النطاق' : 'خارج النطاق') : '',
                $r['out_ok'] === null ? '' : ($r['out_ok'] ? 'داخل النطاق' : 'خارج النطاق'),
                (string) $r['note'],
            ];
        }

        (new SimpleXlsx())
            ->addSheet('الملخص', $summary, [28, 18, 13, 15, 14, 17, 14, 19])
            ->addSheet('التفاصيل', $detail, [28, 18, 13, 11, 12, 20, 10, 13, 14, 14, 34])
            ->save($path);
    }
}
