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
        'legacy_product_id',
        'order_item_id',
        'status',
        'started_at',
        'expires_at',
        'next_renewal_date',
        'auto_renew',
        'renewal_price',
        'renewal_price_locked_at',
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
        'renewal_price_locked_at' => 'datetime',
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
        // Active "thật": status=active VÀ chưa quá hạn (kể cả khi cron chưa kịp mark expired).
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    /** Status thô trong DB (không kiểm tra expires_at). Dùng cho admin/audit. */
    public function scopeActiveRaw(Builder $query): Builder
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

    /** Đang hoạt động THẬT (có quyền dùng dịch vụ): status=active VÀ chưa quá hạn. */
    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        return !$this->expires_at || !$this->expires_at->isPast();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast());
    }

    /** Status hiển thị thực tế (auto-correct nếu DB chưa kịp sync). */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'active' && $this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }
        return $this->status;
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
