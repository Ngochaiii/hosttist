<?php

namespace App\Helpers;

use App\Models\Order_items;

class ServiceHelper
{
    /**
     * Số năm hiển thị cho 1 order item. Đơn gia hạn không tin vào cột
     * duration (dữ liệu cũ trước bản vá có thể đã lưu nhầm số THÁNG thay vì
     * NĂM) mà luôn suy ra từ recurring_period của sản phẩm — tự sửa hiển thị
     * mà không cần cập nhật lại các bản ghi cũ trong DB.
     */
    public static function orderItemDurationYears(Order_items $item): int
    {
        $options = json_decode($item->options, true) ?: [];

        if (!empty($options['renewal'])) {
            $recurringMonths = (int) (optional($item->product)->recurring_period ?? 12);
            return max(1, (int) round($recurringMonths / 12));
        }

        return (int) ($options['period'] ?? $item->duration ?? 1);
    }

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