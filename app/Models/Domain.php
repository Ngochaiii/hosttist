<?php

namespace App\Models;

use App\Models\Traits\EncryptsData;
use Illuminate\Database\Eloquent\Model;

/**
 * Tài sản tên miền của khách (đặt qua web hoặc admin import từ Nhân Hòa).
 *
 * registrant (thông tin chủ thể) và auth_code được MÃ HÓA tại nghỉ (at rest)
 * qua mutator/accessor — không lưu plaintext.
 */
class Domain extends Model
{
    use EncryptsData;

    public const STATUS_PENDING     = 'pending';
    public const STATUS_ACTIVE      = 'active';
    public const STATUS_EXPIRED     = 'expired';
    public const STATUS_CANCELLED   = 'cancelled';
    public const STATUS_TRANSFERRED = 'transferred';

    public const SOURCE_ORDER  = 'customer_order';
    public const SOURCE_IMPORT = 'admin_import';

    protected $fillable = [
        'customer_id', 'order_item_id', 'customer_service_id', 'tld_id',
        'domain_name', 'sld', 'tld',
        'status', 'years', 'registered_at', 'expires_at',
        'cost_price', 'sell_price', 'profit',
        'registrant', 'auth_code',
        'registrar', 'nameservers', 'auto_renew',
        'source', 'notes',
    ];

    protected $casts = [
        'registered_at' => 'date',
        'expires_at'    => 'date',
        'years'         => 'integer',
        'cost_price'    => 'decimal:2',
        'sell_price'    => 'decimal:2',
        'profit'        => 'decimal:2',
        'nameservers'   => 'array',
        'auto_renew'    => 'boolean',
    ];

    // --- Mã hóa thông tin nhạy cảm ---------------------------------------

    public function setRegistrantAttribute($value): void
    {
        $this->attributes['registrant'] = $value === null ? null : $this->encryptField($value);
    }

    public function getRegistrantAttribute($value)
    {
        return $value === null ? null : $this->decryptField($value);
    }

    public function setAuthCodeAttribute($value): void
    {
        $this->attributes['auth_code'] = ($value === null || $value === '') ? null : $this->encryptField($value);
    }

    public function getAuthCodeAttribute($value)
    {
        return $value === null ? null : $this->decryptField($value);
    }

    // --- Quan hệ ---------------------------------------------------------

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function tldRef()
    {
        return $this->belongsTo(DomainTld::class, 'tld_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(Order_items::class, 'order_item_id');
    }

    public function customerService()
    {
        return $this->belongsTo(CustomerService::class, 'customer_service_id');
    }

    // --- Scopes ----------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeExpiringBefore($query, $date)
    {
        return $query->where('status', self::STATUS_ACTIVE)->whereDate('expires_at', '<=', $date);
    }
}
