<?php
// app/Models/ServiceProvision.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\EncryptsData;

class ServiceProvision extends Model
{
    use EncryptsData; // CHỈ giữ EncryptsData

    protected $fillable = [
        'order_item_id',
        'product_id',
        'customer_id',
        'provision_type',
        'provision_status',
        'provision_data',
        'provisioned_by',
        'provisioned_at',
        'provision_notes',
        'delivery_status',
        'delivery_method',
        'delivered_at',
        'view_count',
        'last_viewed_at',
        'external_id',
        'priority',
        'estimated_completion',
        'failure_reason'
    ];

    protected $casts = [
        'provisioned_at' => 'datetime',
        'delivered_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'estimated_completion' => 'datetime',
        'view_count' => 'integer',
        'priority' => 'integer'
    ];

    // XÓA protected $dates

    // Relationships
    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(Order_items::class, 'order_item_id');
    }

    public function logs()
    {
        return $this->hasMany(ProvisionLog::class, 'provision_id');
    }

    // Helper methods
    public function isPending()
    {
        return $this->provision_status === 'pending';
    }

    public function isCompleted()
    {
        return $this->provision_status === 'completed';
    }

    public function isFailed()
    {
        return $this->provision_status === 'failed';
    }

    public function isProcessing()
    {
        return $this->provision_status === 'processing';
    }

    public function isCancelled()
    {
        return $this->provision_status === 'cancelled';
    }

    public function getStatusLabel()
    {
        return match($this->provision_status) {
            'pending' => 'Đang chờ',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'failed' => 'Thất bại',
            'cancelled' => 'Đã hủy',
            default => 'Không xác định'
        };
    }
}