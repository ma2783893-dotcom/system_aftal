<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Finance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary',
        'bonus',
        'deductions',
        'semester_id',
        'hours',
        'hourly_rate',
        'total_due',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
