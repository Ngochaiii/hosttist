<?php
namespace App\Services;

use App\Models\ProvisionLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function log($provisionId, $action, $data = [], $severity = 'info')
    {
        return ProvisionLog::create([
            'provision_id' => $provisionId,
            'action' => $action,
            'performed_by' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'additional_data' => $data,
            'severity' => $severity
        ]);
    }

    public static function logError($provisionId, $action, $errorMessage, $data = [])
    {
        return self::log($provisionId, $action, array_merge($data, ['error' => $errorMessage]), 'error');
    }

    public static function logFormAccess($provisionId, $formType)
    {
        return self::log($provisionId, 'form_accessed', [
            'form_type' => $formType,
            'timestamp' => now()->toISOString()
        ]);
    }

    public static function logFormSubmit($provisionId, $action, $formData)
    {
        // Don't log sensitive data in plain text
        $sanitizedData = self::sanitizeFormData($formData);
        
        return self::log($provisionId, 'form_submitted', [
            'action_type' => $action,
            'form_data' => $sanitizedData,
            'timestamp' => now()->toISOString()
        ]);
    }

    public static function logStatusChange($provisionId, $oldStatus, $newStatus, $reason = null)
    {
        return self::log($provisionId, 'status_changed', [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'timestamp' => now()->toISOString()
        ]);
    }

    private static function sanitizeFormData($data)
    {
        $sensitiveFields = ['password', 'private_key', 'certificate'];
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $sensitiveFields)) {
                $sanitized[$key] = '[ENCRYPTED]';
            } else {
                $sanitized[$key] = is_string($value) && strlen($value) > 100 
                    ? substr($value, 0, 100) . '...' 
                    : $value;
            }
        }
        
        return $sanitized;
    }
}

