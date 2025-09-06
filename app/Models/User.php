<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'username',
        'phone',
        'avatar',
        'address'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationship với Customer
    public function customer()
    {
        return $this->hasOne(Customers::class, 'user_id');
    }

    // Relationship với Order (đơn hàng tạo bởi người dùng)
    public function orders()
    {
        return $this->hasMany(Orders::class, 'created_by');
    }

    // Relationship với Invoice (hóa đơn tạo bởi người dùng)
    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'created_by');
    }

    // Helper method để kiểm tra quyền admin
    public function isAdmin()
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    // Helper method để kiểm tra quyền super admin
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function provisionsHandled()
    {
        return $this->hasMany(ServiceProvision::class, 'provisioned_by');
    }

    public function provisionTemplatesCreated()
    {
        return $this->hasMany(ProvisionTemplate::class, 'created_by');
    }

    public function provisionLogs()
    {
        return $this->hasMany(ProvisionLog::class, 'performed_by');
    }

    public function recentProvisionActivity($days = 7)
    {
        return $this->hasMany(ProvisionLog::class, 'performed_by')
            ->where('created_at', '>=', now()->subDays($days));
    }
}
