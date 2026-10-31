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
        'device_id',
        'device_bound_at',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_id',
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
            'device_bound_at' => 'datetime',
            'must_change_password' => 'boolean',
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

    /**
     * Admins can do everything; supervisors only what was ticked when they were created.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        if ($this->role !== 'supervisor') {
            return false;
        }
        $perms = $this->permissions;
        if (is_string($perms)) {
            $perms = json_decode($perms, true);
            if (is_string($perms)) {           // stored double-encoded
                $perms = json_decode($perms, true);
            }
        }
        return in_array($permission, (array) $perms, true);
    }

    /** Admin, supervisor, or the employee themself may see this person's CV. */
    public function canSeeFilesOf(User $other): bool
    {
        return $this->id === $other->id || $this->isAdmin() || $this->role === 'supervisor';
    }

    public function hasCvFile(): bool
    {
        return \App\Support\FileStore::exists($this->cv, 'cv');
    }

    public function photoUrl(): string
    {
        return $this->profile_photo ? url('/photo/' . $this->id) . '?v=' . substr(md5($this->profile_photo), 0, 8) : '/assets/default-avatar.svg';
    }

    public function roleLabel(): string
    {
        return [
            'admin'      => 'مدير النظام',
            'manager'    => 'مدير',
            'supervisor' => 'مشرف',
            'employee'   => 'موظف',
        ][$this->role] ?? (string) $this->role;
    }
}
