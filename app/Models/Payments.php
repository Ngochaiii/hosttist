<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payments extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'invoice_id',
        'payment_number',
        'amount',
        'payment_method', // bank_transfer, credit_card, cash
        'payment_date',
        'transaction_id',
        'status', // pending, completed, failed, refunded
        'notes',
        'verified_by', // user_id của admin/hệ thống đã xác nhận payment
        'verified_at', // thời điểm xác nhận
    ];

    // Relationship với Order
    public function order()
    {
        return $this->belongsTo(Orders::class);
    }

    // Relationship với Invoice
    public function invoice()
    {
        return $this->belongsTo(Invoices::class);
    }
}
