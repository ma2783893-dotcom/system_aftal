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
        'check_out_time',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance_meters',
        'check_out_verified',
        'device_id',
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
