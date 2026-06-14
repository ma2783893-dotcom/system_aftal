<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'hours_worked',
        'status',
        'check_in_time',
        'latitude',
        'longitude',
        'distance_meters',
        'location_verified',
        'ip_address',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
