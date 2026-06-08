<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Đuôi tên miền + cấu hình giá.
 *
 * Giá bán/lãi KHÔNG lưu cột riêng — tính từ cost + markup qua accessor:
 *   $tld->register_price, $tld->renew_price, $tld->register_profit
 */
class DomainTld extends Model
{
    public const MARKUP_AMOUNT  = 'amount';
    public const MARKUP_PERCENT = 'percent';

    protected $fillable = [
        'tld', 'is_vn',
        'register_cost', 'renew_cost', 'transfer_cost',
        'markup_type', 'markup_value', 'round_to',
        'min_years', 'max_years',
        'product_id', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_vn'         => 'boolean',
        'is_active'     => 'boolean',
        'register_cost' => 'decimal:2',
        'renew_cost'    => 'decimal:2',
        'transfer_cost' => 'decimal:2',
        'markup_value'  => 'decimal:2',
        'round_to'      => 'integer',
        'min_years'     => 'integer',
        'max_years'     => 'integer',
    ];

    protected $appends = ['register_price', 'renew_price', 'transfer_price', 'register_profit'];

    /**
     * Áp markup lên 1 giá gốc → giá bán (đã làm tròn nếu cấu hình round_to).
     * Trả null nếu cost không hợp lệ.
     */
    public function computePrice($cost): ?float
    {
        if ($cost === null) {
            return null;
        }
        $cost   = (float) $cost;
        $markup = (float) $this->markup_value;

        $price = $this->markup_type === self::MARKUP_PERCENT
            ? $cost * (1 + $markup / 100)
            : $cost + $markup;

        if ($this->round_to && $this->round_to > 0) {
            $price = round($price / $this->round_to) * $this->round_to;
        }

        return round($price, 2);
    }

    public function getRegisterPriceAttribute(): ?float
    {
        return $this->computePrice($this->register_cost);
    }

    public function getRenewPriceAttribute(): ?float
    {
        return $this->computePrice($this->renew_cost);
    }

    public function getTransferPriceAttribute(): ?float
    {
        return $this->transfer_cost === null ? null : $this->computePrice($this->transfer_cost);
    }

    /** Lãi trên 1 lượt đăng ký = giá bán - giá gốc. */
    public function getRegisterProfitAttribute(): ?float
    {
        $price = $this->register_price;
        return $price === null ? null : round($price - (float) $this->register_cost, 2);
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'tld_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
