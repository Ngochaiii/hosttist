<?php
// app/Models/Traits/EncryptsData.php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

trait EncryptsData 
{
    /**
     * Encrypt field value
     *
     * @param mixed $value
     * @return string|null
     */
    public function encryptField($value)
    {
        if (!$value) {
            return null;
        }
        
        try {
            return Crypt::encryptString(json_encode($value));
        } catch (\Exception $e) {
            Log::error('Encryption failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Decrypt field value
     *
     * @param string $value
     * @return mixed
     */
    public function decryptField($value)
    {
        if (!$value) {
            return null;
        }

        try {
            return json_decode(Crypt::decryptString($value), true);
        } catch (\Exception $e) {
            Log::error('Decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if field is encrypted
     *
     * @param string $value
     * @return bool
     */
    public function isEncrypted($value)
    {
        if (!$value) {
            return false;
        }

        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Safely encrypt or update encrypted field
     *
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function setEncryptedAttribute($field, $value)
    {
        if ($value !== null) {
            $this->attributes[$field] = $this->encryptField($value);
        }
    }

    /**
     * Safely decrypt field
     *
     * @param string $field
     * @return mixed
     */
    public function getEncryptedAttribute($field)
    {
        return $this->decryptField($this->attributes[$field] ?? null);
    }
}