<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'subject_name',
        'hourly_rate',
        'worked_hours',
        'total_amount',
    ];

    /**
     * Auto-compute total_amount before every save.
     */
    protected static function booted(): void
    {
        static::saving(function (Subject $subject) {
            $subject->total_amount = floatval($subject->hourly_rate) * floatval($subject->worked_hours);
        });
    }

    /**
     * A subject belongs to one employee (User).
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
