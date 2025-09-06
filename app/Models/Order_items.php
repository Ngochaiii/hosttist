<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order_items extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'product_id',
        'name',
        'sku',
        'quantity',
        'price',
        'tax_percent',
        'tax_amount',
        'discount_percent',
        'discount_amount',
        'subtotal',
        'total',
        'options', // JSON
        'duration',
        'domain',
        'service_id', // ID của sản phẩm được tạo ra khi đặt hàng thành công
    ];

    // Relationship với Order
    public function order()
    {
        return $this->belongsTo(Orders::class);
    }

    // Relationship với Product
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    // Relationship với Service (product đã bán)
    public function service()
    {
        return $this->belongsTo(Products::class, 'service_id');
    }

    // Lấy tùy chọn dưới dạng mảng
    public function getOptionsArrayAttribute()
    {
        return json_decode($this->options, true) ?: [];
    }

    public function serviceProvisions()
    {
        return $this->hasMany(ServiceProvision::class, 'order_item_id');
    }

    public function latestProvision()
    {
        return $this->hasOne(ServiceProvision::class, 'order_item_id')->latest();
    }

    public function completedProvisions()
    {
        return $this->hasMany(ServiceProvision::class, 'order_item_id')
            ->where('provision_status', ServiceProvision::STATUS_COMPLETED);
    }
}
