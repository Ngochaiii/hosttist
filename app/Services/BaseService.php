<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class BaseService
{
    /**
     * Execute database transaction with automatic rollback on error
     *
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    protected function transaction(callable $callback)
    {
        try {
            return DB::transaction($callback);
        } catch (Exception $e) {
            Log::error(get_class($this) . ' Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Log service activity
     *
     * @param string $action
     * @param array $data
     * @param string $level
     */
    protected function logActivity(string $action, array $data = [], string $level = 'info')
    {
        Log::log($level, get_class($this) . " - {$action}", $data);
    }

    /**
     * Handle service exceptions with consistent logging
     *
     * @param Exception $e
     * @param string $context
     * @throws Exception
     */
    protected function handleException(Exception $e, string $context = '')
    {
        $contextInfo = $context ? " in {$context}" : '';
        
        Log::error(get_class($this) . " Error{$contextInfo}: " . $e->getMessage(), [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'context' => $context
        ]);
        
        throw $e;
    }

    /**
     * Validate required parameters
     *
     * @param array $data
     * @param array $required
     * @throws Exception
     */
    protected function validateRequired(array $data, array $required)
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                throw new Exception("Required field '{$field}' is missing or empty");
            }
        }
    }

    /**
     * Generate unique number with prefix
     *
     * @param string $prefix
     * @param int $length
     * @return string
     */
    protected function generateUniqueNumber(string $prefix, int $length = 5): string
    {
        return $prefix . '-' . time() . strtoupper(\Illuminate\Support\Str::random($length));
    }
}