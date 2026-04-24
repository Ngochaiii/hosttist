<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoices extends Model
{
    use HasFactory;
    protected $fillable = [
        'invoice_number',
        'order_id',
        'customer_id',
        'status', // draft, sent, paid, overdue, cancelled
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'due_date',
        'notes',
        'created_by', // user_id của người tạo
        'vat_invoice_requested',
        'vat_company_name',
        'vat_tax_code',
        'vat_company_address',
        'vat_company_email',
    ];

    protected $casts = [
        'vat_invoice_requested' => 'boolean',
    ];

    // Relationship với Customer
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id', 'id');
    }

    // Trong model Invoices.php
    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id', 'id');
    }

    // Relationship với User (người tạo)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship với InvoiceItem
    public function items()
    {
        return $this->hasMany(Invoice_items::class);
    }

    // Relationship với Payment
    public function payments()
    {
        return $this->hasMany(Payments::class);
    }
}
