<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeVoucher extends Model
{
    protected $fillable = [
        'employee_id', 'amount', 'description',
        'attachment', 'status', 'created_by', 'unlocked_at',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
