<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class deposits extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'customer_id',
        'amount',
        'payment_method',
        'note',
        'status', // pending, approved, rejected
        'payment_details',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'verified_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====
    
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ===== SCOPES =====
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    // ===== ACCESSORS & METHODS =====
    
    /**
     * Check if deposit is expired (from payment_details)
     */
    public function isExpired(): bool
    {
        if ($this->status !== 'pending') return false;
        
        $paymentDetails = $this->payment_details ?? [];
        
        if (!isset($paymentDetails['expires_at'])) {
            // Default: 30 minutes from creation
            return $this->created_at->addMinutes(30)->isPast();
        }
        
        $expiresAt = Carbon::parse($paymentDetails['expires_at']);
        return $expiresAt->isPast();
    }

    /**
     * Get original amount from payment_details
     */
    public function getOriginalAmount(): float
    {
        $paymentDetails = $this->payment_details ?? [];
        return $paymentDetails['original_amount'] ?? $this->amount;
    }

    /**
     * Get bonus amount from payment_details
     */
    public function getBonusAmount(): float
    {
        $paymentDetails = $this->payment_details ?? [];
        return $paymentDetails['bonus_amount'] ?? 0;
    }

    /**
     * Get bonus percentage from payment_details
     */
    public function getBonusPercent(): float
    {
        $paymentDetails = $this->payment_details ?? [];
        return $paymentDetails['bonus_percent'] ?? 0;
    }

    /**
     * Get currency from payment_details or locale
     */
    public function getCurrency(): string
    {
        $paymentDetails = $this->payment_details ?? [];
        $locale = $paymentDetails['locale'] ?? 'vi';
        return $locale === 'vi' ? 'VND' : 'USD';
    }

    /**
     * Get exchange rate from payment_details
     */
    public function getExchangeRate(): float
    {
        $paymentDetails = $this->payment_details ?? [];
        return $paymentDetails['exchange_rate'] ?? 1;
    }

    /**
     * Get expires at timestamp
     */
    public function getExpiresAt(): ?Carbon
    {
        $paymentDetails = $this->payment_details ?? [];
        
        if (isset($paymentDetails['expires_at'])) {
            return Carbon::parse($paymentDetails['expires_at']);
        }
        
        return $this->created_at->addMinutes(30);
    }

    /**
     * Format amount for display
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => number_format($this->amount, 0, ',', '.') . ' ' . $this->getCurrencySymbol()
        );
    }

    /**
     * Get status color for UI
     */
    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => match($this->status) {
                'pending' => $this->isExpired() ? 'secondary' : 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
                default => 'secondary'
            }
        );
    }

    /**
     * Get status text in Vietnamese
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => match($this->status) {
                'pending' => $this->isExpired() ? 'Đã hết hạn' : 'Chờ thanh toán',
                'approved' => 'Đã duyệt',
                'rejected' => 'Bị từ chối',
                default => 'Không xác định'
            }
        );
    }

    /**
     * Check if deposit is still active (can be paid)
     */
    public function isActive(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    /**
     * Approve deposit
     */
    public function approve(?int $verifiedBy = null): bool
    {
        $updated = $this->update([
            'status' => 'approved',
            'verified_by' => $verifiedBy,
            'verified_at' => now()
        ]);

        if ($updated && $this->customer) {
            // Update customer balance
            $this->customer->increment('balance', $this->amount);
            
            // Log the transaction
            Log::info('Deposit approved', [
                'transaction_code' => $this->transaction_code,
                'customer_id' => $this->customer_id,
                'amount' => $this->amount
            ]);
        }

        return $updated;
    }

    /**
     * Reject deposit
     */
    public function reject(?int $verifiedBy = null, ?string $reason = null): bool
    {
        $paymentDetails = $this->payment_details ?? [];
        if ($reason) {
            $paymentDetails['rejection_reason'] = $reason;
        }

        return $this->update([
            'status' => 'rejected',
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
            'payment_details' => $paymentDetails
        ]);
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol(): string
    {
        return match($this->getCurrency()) {
            'VND' => 'đ',
            'USD' => '$',
            default => ''
        };
    }

    /**
     * Get payment method display name
     */
    public function getPaymentMethodName(): string
    {
        return match($this->payment_method) {
            'bank' => 'Chuyển khoản ngân hàng',
            'momo' => 'Ví MoMo',
            'zalopay' => 'ZaloPay',
            'paypal' => 'PayPal',
            'crypto' => 'Tiền điện tử',
            default => ucfirst($this->payment_method)
        };
    }

    /**
     * Get time remaining until expiration
     */
    public function getTimeRemaining(): ?string
    {
        if ($this->status !== 'pending') {
            return null;
        }

        $expiresAt = $this->getExpiresAt();
        $now = now();
        
        if ($expiresAt->isPast()) {
            return 'Đã hết hạn';
        }

        $diff = $now->diffInMinutes($expiresAt);
        $hours = floor($diff / 60);
        $minutes = $diff % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Get deposits summary for customer
     */
    public static function getSummaryForCustomer(int $customerId): array
    {
        $deposits = static::where('customer_id', $customerId);
        
        return [
            'total_deposits' => $deposits->count(),
            'total_amount' => $deposits->sum('amount'),
            'pending_count' => $deposits->where('status', 'pending')->count(),
            'approved_count' => $deposits->where('status', 'approved')->count(),
            'rejected_count' => $deposits->where('status', 'rejected')->count(),
        ];
    }
}