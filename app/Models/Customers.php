<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/Customer.php
class Customers extends Model
{
    protected $fillable = [
        'user_id', // Liên kết với bảng users
        'company_name',
        'tax_code',
        'business_type',
        'industry',
        'website',
        'status', // active, inactive
        'notes',
        'source', // Nguồn khách hàng: website, referral, etc.
        'balance', // Số dư tài khoản
        'wallet_id', // ID ví điện tử nếu cần
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    // Relationship với User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship với Order
    public function orders()
    {
        return $this->hasMany(Orders::class, 'customer_id');
    }

    // Relationship với Invoice
    public function invoices()
    {
        return $this->hasMany(Invoices::class, 'customer_id', 'id');
    }

    // Relationship với Product (dịch vụ của khách hàng)
    public function services()
    {
        return $this->hasMany(Products::class, 'customer_id');
    }

    // Truy cập các thuộc tính của User thông qua Customer
    public function getNameAttribute()
    {
        return $this->user->name;
    }

    public function getEmailAttribute()
    {
        return $this->user->email;
    }

    public function getPhoneAttribute()
    {
        return $this->user->phone;
    }

    public function getAddressAttribute()
    {
        return $this->user->address;
    }

    // Phương thức cập nhật số dư
    public function updateBalance($amount)
    {
        $this->balance += $amount;
        return $this->save();
    }

    // Phương thức kiểm tra số dư
    public function hasBalance($amount)
    {
        return $this->balance >= $amount;
    }

    // Hiển thị số dư đã định dạng
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 0, ',', '.') . ' đ';
    }

    public function serviceProvisions()
    {
        return $this->hasMany(ServiceProvision::class, 'customer_id');
    }

    public function completedProvisions()
    {
        return $this->hasMany(ServiceProvision::class, 'customer_id')
            ->where('provision_status', ServiceProvision::STATUS_COMPLETED);
    }

    public function pendingProvisions()
    {
        return $this->hasMany(ServiceProvision::class, 'customer_id')
            ->where('provision_status', ServiceProvision::STATUS_PENDING);
    }

    public function provisionLogs()
    {
        return $this->hasManyThrough(
            ProvisionLog::class,
            ServiceProvision::class,
            'customer_id', // Foreign key on provisions table
            'provision_id', // Foreign key on logs table
            'id', // Local key on customers table
            'id' // Local key on provisions table
        );
    }
}
