<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CustomerService extends Model
{
    protected $fillable = [
        'customer_id',
        'provision_id',
        'product_id',
        'order_item_id',
        'status',
        'started_at',
        'expires_at',
        'next_renewal_date',
        'auto_renew',
        'renewal_price',
        'billing_cycle',
        'notified_30d_at',
        'notified_15d_at',
        'notified_7d_at',
        'notified_1d_at',
        'notes',
    ];

    protected $casts = [
        'started_at'       => 'datetime',
        'expires_at'       => 'datetime',
        'next_renewal_date' => 'datetime',
        'notified_30d_at'  => 'datetime',
        'notified_15d_at'  => 'datetime',
        'notified_7d_at'   => 'datetime',
        'notified_1d_at'   => 'datetime',
        'auto_renew'       => 'boolean',
        'renewal_price'    => 'decimal:2',
    ];

    // ===== Relationships =====

    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    public function provision()
    {
        return $this->belongsTo(ServiceProvision::class, 'provision_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(Order_items::class, 'order_item_id');
    }

    // ===== Scopes =====

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon(Builder $query, int $days): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    // ===== Helpers =====

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast());
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return (int) now()->diffInDays($this->expires_at, false);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'Đang hoạt động',
            'expired'   => 'Đã hết hạn',
            'suspended' => 'Tạm ngừng',
            'cancelled' => 'Đã hủy',
            default     => 'Không xác định',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'success',
            'expired'   => 'danger',
            'suspended' => 'warning',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }
}
