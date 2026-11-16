<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class StaffRequest extends Model
{
    protected $table = 'staff_requests';

    protected $fillable = [
        'user_id', 'type', 'date_from', 'date_to', 'time', 'reason', 'email',
        'status', 'admin_note', 'decided_by', 'decided_at', 'ip_address',
    ];

    protected $casts = [
        'date_from'  => 'date',
        'date_to'    => 'date',
        'decided_at' => 'datetime',
    ];

    public const TYPES = [
        'leave'    => 'إجازة',
        'late'     => 'إذن تأخير',
        'early'    => 'إذن خروج مبكر',
        'password' => 'نسيت كلمة المرور',
    ];

    public const STATUSES = [
        'pending'  => 'بانتظار الرد',
        'approved' => 'تمت الموافقة',
        'rejected' => 'مرفوض',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Short Arabic description of what was asked for. */
    public function summary(): string
    {
        $d = fn ($c) => $c ? $c->format('d/m/Y') : '';
        return match ($this->type) {
            'leave' => ($this->date_to && !$this->date_to->eq($this->date_from))
                ? 'من ' . $d($this->date_from) . ' إلى ' . $d($this->date_to) . ' (' . ($this->date_from->diffInDays($this->date_to) + 1) . ' أيام)'
                : 'يوم ' . $d($this->date_from),
            'late'  => 'يوم ' . $d($this->date_from) . ' — الحضور حتى الساعة ' . $this->time,
            'early' => 'يوم ' . $d($this->date_from) . ' — الخروج الساعة ' . $this->time,
            default => (string) $this->email,
        };
    }

    /** Creates the table on first use if the migration has not been run yet. */
    public static function ensureTable(): bool
    {
        try {
            if (!Schema::hasTable('staff_requests')) {
                Artisan::call('migrate', ['--force' => true]);
            }
            return Schema::hasTable('staff_requests');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function pendingCount(): int
    {
        try {
            return Schema::hasTable('staff_requests') ? static::where('status', 'pending')->count() : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
