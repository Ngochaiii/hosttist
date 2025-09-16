<?php

namespace App\Helpers;

class ServiceHelper
{
    public static function getStatusColor($status)
    {
        return match($status) {
            'pending' => 'secondary',
            'processing' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary'
        };
    }

    public static function getServiceStatusColor($status)
    {
        return match($status) {
            'active' => 'success',
            'expired' => 'warning',
            'suspended' => 'warning',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }
}