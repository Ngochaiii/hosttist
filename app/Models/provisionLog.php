<?php
// app/Models/ProvisionLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\EncryptsData;

class ProvisionLog extends Model  // Chú ý: tên class phải viết hoa
{
    use EncryptsData;

    protected $fillable = [
        'provision_id',
        'action',
        'performed_by',
        'ip_address',
        'user_agent',
        'additional_data',
        'severity',
        'error_message'
    ];

    protected $casts = [
        'additional_data' => 'array'
    ];

    // Relationships
    public function provision()
    {
        return $this->belongsTo(ServiceProvision::class, 'provision_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Helper methods
    public function getSeverityColor()
    {
        return match($this->severity) {
            'info' => 'blue',
            'warning' => 'yellow',
            'error' => 'red',
            default => 'gray'
        };
    }

    public function getActionLabel()
    {
        return match($this->action) {
            'created' => 'Tạo mới',
            'viewed' => 'Xem',
            'completed' => 'Hoàn thành',
            'failed' => 'Thất bại',
            default => ucfirst(str_replace('_', ' ', $this->action))
        };
    }
}