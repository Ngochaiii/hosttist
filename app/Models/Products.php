<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;
    protected $fillable = [
        'category_id',
        'customer_id', // Chủ sở hữu (nếu là dịch vụ đã bán)
        'parent_product_id', // Sản phẩm cha (nếu là biến thể)
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'sale_price',
        'image',
        'type', // product, service, ssl, domain, hosting
        'product_status', // active, inactive, draft (trạng thái sản phẩm để bán)
        'service_status', // pending, active, expired, cancelled (trạng thái dịch vụ đã mua)
        'stock', // -1 = unlimited
        'start_date', // Ngày bắt đầu dịch vụ
        'end_date', // Ngày kết thúc dịch vụ
        'next_due_date', // Ngày thanh toán tiếp theo
        'is_recurring', // Dịch vụ có định kỳ không
        'recurring_period', // Chu kỳ định kỳ (tháng)
        'auto_renew', // Tự động gia hạn
        'meta_data', // JSON (thông tin thêm như domain, certificate key, server info)
        'options', // JSON (các tùy chọn)
        'is_featured',
        'sort_order',
    ];

    // Relationship với Category
    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    // Relationship với Customer (chủ sở hữu)
    public function customer()
    {
        return $this->belongsTo(Customers::class);
    }

    // Relationship với Product cha
    public function parentProduct()
    {
        return $this->belongsTo(Products::class, 'parent_product_id');
    }

    // Relationship với các biến thể của Product
    public function variants()
    {
        return $this->hasMany(Products::class, 'parent_product_id');
    }

    // Relationship với OrderItem
    public function orderItems()
    {
        return $this->hasMany(Order_items::class, 'product_id'); // Thay 'product_id' bằng tên cột thực tế
    }

    // Relationship với InvoiceItem
    public function invoiceItems()
    {
        return $this->hasMany(Order_items::class);
    }

    // Lấy meta data dưới dạng mảng
    public function getMetaDataArrayAttribute()
    {
        return json_decode($this->meta_data, true) ?: [];
    }

    // Lấy tùy chọn dưới dạng mảng
    public function getOptionsArrayAttribute()
    {
        return json_decode($this->options, true) ?: [];
    }

    // Scope lấy các sản phẩm để bán
    public function scopeForSale($query)
    {
        return $query->whereNull('customer_id')
            ->where('product_status', 'active');
    }

    // Scope lấy dịch vụ đã bán
    public function scopeServices($query)
    {
        return $query->whereNotNull('customer_id');
    }

    // Scope lọc theo loại
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope lọc theo trạng thái dịch vụ
    public function scopeWithServiceStatus($query, $status)
    {
        return $query->where('service_status', $status);
    }
    // Accessor để tự động chuyển text thành array/object khi lấy dữ liệu
    public function getMetaDataAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    // Mutator để tự động chuyển array/object thành JSON string khi lưu
    public function setMetaDataAttribute($value)
    {
        $this->attributes['meta_data'] = is_array($value) ? json_encode($value) : $value;
    }

    // Tương tự cho options
    public function getOptionsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setOptionsAttribute($value)
    {
        $this->attributes['options'] = is_array($value) ? json_encode($value) : $value;
    }

    public function serviceProvisions()
    {
        return $this->hasMany(ServiceProvision::class, 'product_id');
    }

    public function provisionTemplates()
    {
        return $this->hasMany(ProvisionTemplate::class, 'product_id');
    }

    public function activeTemplates()
    {
        return $this->hasMany(ProvisionTemplate::class, 'product_id')
            ->where('is_active', true);
    }

    public function defaultTemplate()
    {
        return $this->hasOne(ProvisionTemplate::class, 'product_id')
            ->where('is_active', true)
            ->orderBy('priority', 'desc');
    }
}
