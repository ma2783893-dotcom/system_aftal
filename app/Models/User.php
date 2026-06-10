<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'specialization',
        'cv',
        'profile_photo',
        'permissions',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'employee_id');
    }

    /**
     * Grand total salary = sum of all subject totals.
     */
    public function grandTotalSalary(): float
    {
        return (float) $this->subjects->sum('total_amount');
    }

    public function finance()
    {
        return $this->hasOne(Finance::class, 'employee_id');
    }

    public function isAdmin()
    {
        return in_array($this->role, ['admin', 'manager']);
    }
}
